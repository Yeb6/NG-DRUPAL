<?php

namespace Drupal\fs_article_tools\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'related_articles' field type.
 *
 * @FieldType(
 *   id = "articles_hierarchy",
 *   label = @Translation("Articles Heirarchy"),
 *   description = @Translation("Custom field to select related articles in parent-child hierarchy."),
 *   default_widget = "article_hierarchy_widget",
 *   default_formatter = "articles_hierarchy_formatter"
 * )
 */
class ArticleHierarchyItems extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Article Hierarchy NID'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }
}
