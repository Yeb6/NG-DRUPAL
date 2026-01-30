<?php

namespace Drupal\fs_article_tools\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'article_hierarchy_widget' widget.
 *
 * @FieldWidget(
 *   id = "article_hierarchy_widget",
 *   label = @Translation("Articles Hierarchy"),
 *   field_types = {
 *     "articles_hierarchy"
 *   }
 * )
 */
class ArticleHierarchyWidget extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $current_nid = !empty($form['#node']) ? $form['#node']->id() : NULL;

    // Load all fs_article nodes
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'fs_article')
      ->accessCheck(TRUE)
      ->execute();

    $nodes = Node::loadMultiple($nids);
    $structured_nodes = [];

    foreach ($nodes as $node) {

      $title = $node->label();
      $parent_identifier = '';
      $article_identifier = '';
      $root_parent = $title;

      // Extract hierarchy info from taxonomy term
      if ($node->hasField('field_hierarchy') && !$node->get('field_hierarchy')->isEmpty()) {
        $terms = $node->get('field_hierarchy')->referencedEntities();
        $term = reset($terms);

        if ($term instanceof Term) {
          // identifier
          if ($term->hasField('field_identifier') && !$term->get('field_identifier')->isEmpty()) {
            $article_identifier = trim((string) $term->get('field_identifier')->value);
          }

          $identifiers = [];
          for ($i = 1; $i <= 10; $i++) {
            $field = "field_parentidentifier{$i}";
            if ($term->hasField($field) && !$term->get($field)->isEmpty()) {
              $value = trim((string) $term->get($field)->value);
              if ($value !== '' && strtolower($value) !== 'root') $identifiers[] = $value;
            }
          }

          $unique_identifiers = array_values(array_unique($identifiers));
          if (!empty($unique_identifiers)) {
            if (empty($article_identifier)) $article_identifier = end($unique_identifiers);
            $parent_identifier = count($unique_identifiers) > 1 ? $unique_identifiers[count($unique_identifiers) - 2] : '';
            $root_parent = $unique_identifiers[0];
          }
        }
      }

      $article_type = $node->hasField('field_article_type') && !$node->get('field_article_type')->isEmpty()
        ? $node->get('field_article_type')->value
        : '';

      $structured_nodes[] = [
        'nid' => $node->id(),
        'title' => $title,
        'type' => $article_type,
        'identifier' => $article_identifier,
        'parent_identifier' => $parent_identifier,
      ];
    }

    // Build jsTree data
    $treeData = [];
    foreach ($structured_nodes as $node) {
      $parentId = '#'; // top-level default
      if (!empty($node['parent_identifier'])) {
        foreach ($structured_nodes as $potentialParent) {
          if ($potentialParent['identifier'] === $node['parent_identifier']) {
            $parentId = (string) $potentialParent['nid'];
            break;
          }
        }
      }

      $treeData[] = [
        'id' => (string) $node['nid'],
        'parent' => $parentId,
        'text' => $node['title'],
        'type' => $node['type'],
        'nid' => $node['nid'],
      ];
    }
    //echo '<pre>';print_r($treeData);die;
    // Attach jsTree
    $element['#attached']['library'][] = 'fs_article_tools/jstree_widget';
    $element['#attached']['drupalSettings']['fs_article_tools'] = [
      'treeData' => $treeData,
      'currentNid' => $current_nid,
    ];

    // Hidden input for selected node
    $default_value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element['value'] = [
      '#type' => 'hidden',
      '#default_value' => $default_value,
      '#attributes' => ['class' => ['js-article-tree-value']],
    ];

    // Tree container
    $element['tree_headline'] = [
      '#type' => 'markup',
      '#markup' => '<div class="form-item__label">Article Hierarchy</div>',
    ];
    $element['tree'] = [
      '#type' => 'markup',
      '#markup' => '<div id="article-jstree" style="border:1px solid #ccc; padding:8px;"></div>',
    ];

    return $element;
  }
}
