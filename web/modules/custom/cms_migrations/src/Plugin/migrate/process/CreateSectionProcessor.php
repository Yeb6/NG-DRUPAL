<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to handle create section data from articles array.
 *
 * @MigrateProcessPlugin(
 *   id = "create_section_processor"
 * )
 */
class CreateSectionProcessor extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If no value provided, return empty array
    if (empty($value) || !is_array($value)) {
      return [];
    }

    $create_sections = [];
    $current_heading = null;
    $current_content = null;

    // Process articles array to pair headings with content
    foreach ($value as $index => $article) {
      if (!is_array($article) || !isset($article['content'])) {
        continue;
      }

      $content = trim($article['content']);
      
      // Check if this is a heading (contains <p><b>...</b></p>)
      if (preg_match('/<p><b>(.*?)<\/b><\/p>/', $content, $matches)) {
        // If we have a previous heading-content pair, save it
        if ($current_heading && $current_content) {
          $create_sections[] = $this->createSectionParagraph($current_heading, $current_content);
        }
        
        // Start new heading
        $current_heading = trim(strip_tags($matches[1]));
        $current_content = '';
      } else {
        // This is content, add it to current content
        if ($current_heading) {
          $current_content .= $content;
        }
      }
    }

    // Don't forget the last pair
    if ($current_heading && $current_content) {
      $create_sections[] = $this->createSectionParagraph($current_heading, $current_content);
    }

    \Drupal::logger('cms_migrations')->debug('Processed @count create sections from articles array', [
      '@count' => count($create_sections),
    ]);

    return $create_sections;
  }

  /**
   * Create a section paragraph from heading and content.
   *
   * @param string $heading
   *   The heading text.
   * @param string $content
   *   The content text.
   *
   * @return array
   *   Paragraph data structure for "Create Section" paragraph.
   */
  protected function createSectionParagraph($heading, $content) {
    return [
      'type' => 'create_section',
      'field_category_selection' => $heading,
      'field_content' => trim($content),
    ];
  }

}
