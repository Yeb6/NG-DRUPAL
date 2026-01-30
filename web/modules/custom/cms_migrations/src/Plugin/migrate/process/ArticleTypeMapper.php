<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to map template names to article type field values.
 *
 * @MigrateProcessPlugin(
 *   id = "article_type_mapper"
 * )
 */
class ArticleTypeMapper extends ProcessPluginBase {

  /**
   * Template name to article type mapping.
   *
   * @var array
   */
  protected $templateMapping = [
    'pt_red_article' => 'ews_article',
    'pt_green_article' => 'ews_article',
    'pt_chapter' => 'chapter',
    'pt_start' => 'home',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get the source field value if not directly provided
    $source_field = $this->configuration['source_field'] ?? 'templateName';
    $template_name = !empty($value) ? $value : $row->getSourceProperty($source_field);
    
    // If no value provided, return default article type
    if (empty($template_name)) {
      \Drupal::logger('cms_migrations')->warning('Empty template name, using default ews_article');
      return 'ews_article';
    }

    // Clean the value
    $template_name = trim($template_name);
    
    \Drupal::logger('cms_migrations')->info('ArticleTypeMapper processing template: @template', [
      '@template' => $template_name,
    ]);
    
    // Check if we have a mapping for this template
    if (isset($this->templateMapping[$template_name])) {
      $article_type = $this->templateMapping[$template_name];
      \Drupal::logger('cms_migrations')->info('Mapped template @template to article type @type', [
        '@template' => $template_name,
        '@type' => $article_type,
      ]);
      return $article_type;
    }

    // If no mapping found, log warning and return default
    \Drupal::logger('cms_migrations')->warning('No article type mapping found for template "@template", using default ews_article', [
      '@template' => $template_name,
    ]);
    
    return 'ews_article'; // Default to ews_article
  }

}
