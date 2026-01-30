<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to map template names to Drupal content types.
 *
 * @MigrateProcessPlugin(
 *   id = "template_name_mapper"
 * )
 */
class TemplateNameMapper extends ProcessPluginBase {

  /**
   * Template name to content type mapping.
   *
   * @var array
   */
  protected $templateMapping = [
    'pt_red_article' => 'fs_article',
    'pt_start' => 'fs_article', // Default to fs_article for pt_start
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If no value provided, return default content type
    if (empty($value)) {
      return 'fs_article'; // Default to fs_article
    }

    // Clean the value
    $template_name = trim($value);
    
    // Check if we have a mapping for this template
    if (isset($this->templateMapping[$template_name])) {
      $content_type = $this->templateMapping[$template_name];
      \Drupal::logger('cms_migrations')->debug('Mapped template @template to content type @type', [
        '@template' => $template_name,
        '@type' => $content_type,
      ]);
      return $content_type;
    }

    // If no mapping found, log warning and return default
    \Drupal::logger('cms_migrations')->warning('No template mapping found for @template, using default', [
      '@template' => $template_name,
    ]);
    
    return 'fs_article'; // Default to fs_article
  }

}
