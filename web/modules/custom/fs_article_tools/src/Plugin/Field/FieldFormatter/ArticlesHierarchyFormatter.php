<?php

namespace Drupal\fs_article_tools\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'articles_hierarchy_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "articles_hierarchy_formatter",
 *   label = @Translation("Articles Hierarchy Formatter"),
 *   field_types = {
 *     "articles_hierarchy"
 *   }
 * )
 */
class ArticlesHierarchyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->value,
      ];
    }

    return $elements;
  }

}
