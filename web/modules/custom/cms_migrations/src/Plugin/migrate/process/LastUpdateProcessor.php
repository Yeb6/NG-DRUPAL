<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to handle last release article and create Last Update checkbox.
 *
 * @MigrateProcessPlugin(
 *   id = "last_update_processor"
 * )
 */
class LastUpdateProcessor extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If no value provided, return false (checkbox unchecked)
    if (empty($value)) {
      return FALSE;
    }

    // Clean the value
    $last_release_article = trim($value);
    
    // Check if this matches the current article's _id
    $current_id = $row->getSourceProperty('_id');
    
    if ($current_id && $last_release_article === $current_id) {
      \Drupal::logger('cms_migrations')->debug('Last Update checkbox will be checked for article @id', [
        '@id' => $current_id,
      ]);
      return TRUE; // Checkbox should be checked
    }

    \Drupal::logger('cms_migrations')->debug('Last Update checkbox will be unchecked for article @id (last_release_article: @last)', [
      '@id' => $current_id,
      '@last' => $last_release_article,
    ]);
    
    return FALSE; // Checkbox should be unchecked
  }

}
