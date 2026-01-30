<?php

namespace Drupal\fs_article_tools\FormAlter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Service to handle FS Article form submission.
 */
class MBArticleFormSubmit {

  /**
   * Handle form submission for FS Article.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $form_state->getFormObject()->getEntity();
    $node_title = $node->label();

    $related_value = $form_state->getValue('field_article_hierarchy')[0]['value'] ?? [];

    // Default term fields.
    $term_fields = [
      'vid' => 'hierarchy',
      'name' => $node_title,
    ];

    // Step 1: Determine parent chain
    $chain = [];
    if ($related_value != $node->id()) {
      if (!empty($related_value)) {
        $related_node = Node::load($related_value);

        if ($related_node instanceof Node) {
          $parent_title = $related_node->label();

          // Load hierarchy from parent node if exists
          if ($related_node->hasField('field_hierarchy') && !$related_node->get('field_hierarchy')->isEmpty()) {
            $related_terms = $related_node->get('field_hierarchy')->referencedEntities();
            $attached_term = reset($related_terms);

            if ($attached_term instanceof Term) {
              for ($i = 1; $i <= 10; $i++) {
                $field = "field_parentidentifier{$i}";
                if ($attached_term->hasField($field) && !$attached_term->get($field)->isEmpty()) {
                  $value = (string) $attached_term->get($field)->value;
                  if (!empty($value) && !in_array($value, $chain)) {
                    $chain[] = $value;
                  }
                }
              }
            }
          }

          // Add immediate parent and current node
          if (!in_array($parent_title, $chain)) {
            $chain[] = $parent_title;
          }
          if (!in_array($node_title, $chain)) {
            $chain[] = $node_title;
          }
        }
      }
      else {
        // Top-level node: set default parentidentifier values
        $term_fields['field_parentidentifier10'] = $node_title;
        $term_fields['field_parentidentifier9']  = $node_title;
        $term_fields['field_parentidentifier8']  = 'Root';
      }

      // If chain exists (child node), map values in reverse to field_parentidentifier10 → 1
      if (!empty($chain)) {
        $reversed = array_reverse($chain);
        $max_fields = min(10, count($reversed));

        for ($i = 0; $i < $max_fields; $i++) {
          $field = "field_parentidentifier" . (10 - $i);
          $term_fields[$field] = $reversed[$i];
        }
      }

      // Step 2: Attach or update term
      if (!$node->isNew() && $node->hasField('field_hierarchy') && !$node->get('field_hierarchy')->isEmpty()) {
        $existing_terms = $node->get('field_hierarchy')->referencedEntities();
        $term = reset($existing_terms);

        if ($term instanceof Term) {
          foreach ($term_fields as $key => $value) {
            if ($term->hasField($key)) {
              $term->set($key, $value);
            }
          }
          $term->save();
        }
        else {
          $term = Term::create($term_fields);
          $term->save();
          $node->set('field_hierarchy', [['target_id' => $term->id()]]);
        }
      }
      else {
        $term = Term::create($term_fields);
        $term->save();
        $node->set('field_hierarchy', [['target_id' => $term->id()]]);
      }

      // Save the node
      $node->save();
    }
  }
}
