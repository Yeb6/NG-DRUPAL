<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to map JSON fields to Drupal fields.
 *
 * @MigrateProcessPlugin(
 *   id = "json_field_mapper"
 * )
 */
class JsonFieldMapper extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get the source field name from configuration
    $source_field = $this->configuration['source_field'] ?? $destination_property;

    // Get the value from the row
    $source_value = $row->getSourceProperty($source_field);

    // Handle different data types
    if (is_array($source_value)) {
      // For array values, convert to string or handle as needed
      if (empty($source_value)) {
        return NULL;
      }

      // If it's an array of strings, join them
      if (is_array($source_value) && !empty($source_value) && is_string($source_value[0])) {
        return implode(', ', $source_value);
      }

      // For complex arrays, serialize or handle differently
      return json_encode($source_value);
    }

    // Handle boolean values
    if (is_bool($source_value)) {
      return $source_value ? 1 : 0;
    }

    // Handle numeric timestamps
    if (is_numeric($source_value) && $source_value > 1000000000000) {
      // Convert timestamp to ISO date format (cast to int to avoid precision warning)
      $timestamp = (int) ($source_value / 1000);
      return date('Y-m-d\TH:i:s', $timestamp);
    }

    // Return the value as is for strings and other types
    return $source_value;
  }

}
