<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to handle supplementary topics data from articles array.
 *
 * @MigrateProcessPlugin(
 *   id = "supplementary_topics_processor"
 * )
 */
class SupplementaryTopicsProcessor extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If no value provided, return empty array
    if (empty($value) || !is_array($value)) {
      return [];
    }

    $supplementary_topics = [];
    $current_title = null;
    $current_link = null;

    // Process articles array to find supplementary topics title/link pairs
    foreach ($value as $article) {
      if (!is_array($article)) {
        continue;
      }

      // Check for supplementary topics title
      if (isset($article['supplementarytopicstitle'])) {
        // If we have a previous complete pair, save it
        if ($current_title && $current_link) {
          $supplementary_topics[] = [
            'title' => $current_title,
            'fs_id' => $current_link,
          ];
        }
        
        // Start new title
        $current_title = $article['supplementarytopicstitle'];
        $current_link = null;
      }

      // Check for supplementary topics link
      if (isset($article['supplementarytopicslink'])) {
        $current_link = $article['supplementarytopicslink'];
        
        // If we have both title and link, save the pair
        if ($current_title && $current_link) {
          $supplementary_topics[] = [
            'title' => $current_title,
            'fs_id' => $current_link,
          ];
          
          // Reset for next pair
          $current_title = null;
          $current_link = null;
        }
      }
    }

    // Don't forget the last pair if it exists
    if ($current_title && $current_link) {
      $supplementary_topics[] = [
        'title' => $current_title,
        'fs_id' => $current_link,
      ];
    }

    \Drupal::logger('cms_migrations')->debug('Processed @count supplementary topics pairs', [
      '@count' => count($supplementary_topics),
    ]);

    // Log the pairs for debugging
    foreach ($supplementary_topics as $index => $topic) {
      \Drupal::logger('cms_migrations')->debug('Supplementary topic @index: @title -> @fs_id', [
        '@index' => $index,
        '@title' => $topic['title'],
        '@fs_id' => $topic['fs_id'],
      ]);
    }

    // Serialize the data for temporary storage
    return !empty($supplementary_topics) ? serialize($supplementary_topics) : NULL;
  }


}
