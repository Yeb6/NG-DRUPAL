<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Process plugin to create paragraph entities.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraph_creator"
 * )
 */
class ParagraphCreator extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Debug what we're receiving from source
    \Drupal::logger('cms_migrations')->debug('ParagraphCreator received value: @value', [
      '@value' => is_array($value) ? 'array with ' . count($value) . ' items' : gettype($value) . ': ' . (is_string($value) ? substr($value, 0, 100) : $value),
    ]);

    // Debug the actual source data from row
    $source_articles = $row->getSourceProperty('articles');
    \Drupal::logger('cms_migrations')->debug('Source articles from row: @articles', [
      '@articles' => is_array($source_articles) ? 'array with ' . count($source_articles) . ' items' : gettype($source_articles) . ': ' . (is_string($source_articles) ? substr($source_articles, 0, 100) : $source_articles),
    ]);

    // Debug all available source properties
    $all_source = $row->getSource();
    \Drupal::logger('cms_migrations')->debug('All source properties: @properties', [
      '@properties' => implode(', ', array_keys($all_source)),
    ]);

    // Check if articles field exists in source
    if (isset($all_source['articles'])) {
      $articles_data = $all_source['articles'];
      \Drupal::logger('cms_migrations')->debug('Articles field exists in source with @count items', [
        '@count' => is_array($articles_data) ? count($articles_data) : 'not array',
      ]);
      
      // Show first few articles content
      if (is_array($articles_data) && !empty($articles_data)) {
        foreach (array_slice($articles_data, 0, 3) as $index => $article) {
          \Drupal::logger('cms_migrations')->debug('Source article @index: @content', [
            '@index' => $index,
            '@content' => substr($article['content'] ?? 'no content', 0, 100),
          ]);
        }
      }
    } else {
      \Drupal::logger('cms_migrations')->debug('Articles field does not exist in source data');
    }

    // Try to use source data if available, otherwise use static test content
    $articles_to_process = null;
    
    if (!empty($value) && is_array($value)) {
      \Drupal::logger('cms_migrations')->debug('Using source articles data with @count items', [
        '@count' => count($value),
      ]);
      $articles_to_process = $value;
    } elseif (!empty($source_articles) && is_array($source_articles)) {
      \Drupal::logger('cms_migrations')->debug('Using row articles data with @count items', [
        '@count' => count($source_articles),
      ]);
      $articles_to_process = $source_articles;
    } else {
      \Drupal::logger('cms_migrations')->debug('No source articles data found, returning empty');
      // Return empty array if no articles data found
      return [];
    }

    // Process articles array to create sections
    $create_sections = $this->processArticlesToSections($articles_to_process);
    
    if (empty($create_sections)) {
      \Drupal::logger('cms_migrations')->debug('No create sections found in articles data');
      return [];
    }


    $paragraph_references = [];

    foreach ($create_sections as $section_data) {
      if (!is_array($section_data) || !isset($section_data['type'])) {
        continue;
      }

      try {
        // Create the paragraph entity
        $paragraph = Paragraph::create([
          'type' => $section_data['type'],
        ]);

        // Debug paragraph fields
        $field_definitions = $paragraph->getFieldDefinitions();
        \Drupal::logger('cms_migrations')->debug('Paragraph fields: @fields', [
          '@fields' => implode(', ', array_keys($field_definitions)),
        ]);

        // Check specific fields
        if (isset($field_definitions['field_category_selection'])) {
          \Drupal::logger('cms_migrations')->debug('field_category_selection exists');
        } else {
          \Drupal::logger('cms_migrations')->warning('field_category_selection does not exist');
        }

        if (isset($field_definitions['field_content'])) {
          \Drupal::logger('cms_migrations')->debug('field_content exists');
        } else {
          \Drupal::logger('cms_migrations')->warning('field_content does not exist');
        }

        // Set the fields
        foreach ($section_data as $field_name => $field_value) {
          if ($field_name !== 'type') {
            if ($paragraph->hasField($field_name)) {
              // Handle different field types
              if ($field_name === 'field_category_selection') {
                // For list field, check allowed values and add if needed
                $this->ensureCategoryValueExists($field_value);
                $paragraph->set($field_name, $field_value);
                \Drupal::logger('cms_migrations')->debug('Set field @field to @value', [
                  '@field' => $field_name,
                  '@value' => $field_value,
                ]);
              } elseif ($field_name === 'field_content') {
                // For formatted text field, set with format
                $paragraph->set($field_name, [
                  'value' => $field_value,
                  'format' => 'full_html',
                ]);
                \Drupal::logger('cms_migrations')->debug('Set field @field to @value with format', [
                  '@field' => $field_name,
                  '@value' => substr($field_value, 0, 100),
                ]);
              } else {
                $paragraph->set($field_name, $field_value);
                \Drupal::logger('cms_migrations')->debug('Set field @field to @value', [
                  '@field' => $field_name,
                  '@value' => $field_value,
                ]);
              }
            } else {
              \Drupal::logger('cms_migrations')->warning('Paragraph does not have field @field', [
                '@field' => $field_name,
              ]);
            }
          }
        }

        // Save the paragraph
        $paragraph->save();

        // Add to references array
        $paragraph_references[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];

        \Drupal::logger('cms_migrations')->debug('Created paragraph @id of type @type with category @category', [
          '@id' => $paragraph->id(),
          '@type' => $section_data['type'],
          '@category' => $section_data['field_category_selection'] ?? 'unknown',
        ]);

        // Debug field values
        \Drupal::logger('cms_migrations')->debug('Paragraph @id field values: category=@category, content=@content', [
          '@id' => $paragraph->id(),
          '@category' => $paragraph->get('field_category_selection')->value ?? 'not set',
          '@content' => substr($paragraph->get('field_content')->value ?? 'not set', 0, 100),
        ]);

      } catch (\Exception $e) {
        \Drupal::logger('cms_migrations')->error('Failed to create paragraph: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    }

    \Drupal::logger('cms_migrations')->debug('Created @count paragraph references', [
      '@count' => count($paragraph_references),
    ]);

    return $paragraph_references;
  }

  /**
   * Process articles array to create sections.
   *
   * @param array $articles
   *   The articles array from JSON.
   *
   * @return array
   *   Array of section data for paragraph creation.
   */
  protected function processArticlesToSections(array $articles) {
    $create_sections = [];
    $current_heading = null;
    $current_content = null;

    \Drupal::logger('cms_migrations')->info('Processing @count articles to create sections', [
      '@count' => count($articles),
    ]);

    // Process articles array to pair headings with content
    foreach ($articles as $index => $article) {
      if (!is_array($article) || !isset($article['content'])) {
        \Drupal::logger('cms_migrations')->debug('Article @index has no content field, skipping', [
          '@index' => $index,
        ]);
        continue;
      }

      $content = $article['content']; // Don't trim yet to preserve whitespace
      
      \Drupal::logger('cms_migrations')->debug('Article @index raw content: @preview', [
        '@index' => $index,
        '@preview' => substr($content, 0, 150),
      ]);
      
      $content = trim($content); // Trim after logging
      
      // Check if this is a heading (contains <p><b>...</b></p>)
      if (preg_match('/<p><b>(.*?)<\/b><\/p>/', $content, $matches)) {
        // If we have a previous heading-content pair, save it
        if ($current_heading) {
          if ($current_content) {
            \Drupal::logger('cms_migrations')->info('Saving section: heading=@heading, content_length=@length', [
              '@heading' => $current_heading,
              '@length' => strlen($current_content),
            ]);
            $create_sections[] = $this->createSectionParagraph($current_heading, $current_content);
          } else {
            \Drupal::logger('cms_migrations')->warning('Heading "@heading" has no content, skipping', [
              '@heading' => $current_heading,
            ]);
          }
        }
        
        // Start new heading
        $current_heading = trim(strip_tags($matches[1]));
        $current_content = '';
        \Drupal::logger('cms_migrations')->info('Found heading: @heading', [
          '@heading' => $current_heading,
        ]);
      } else {
        // This is content, add it to current content
        if ($current_heading) {
          $current_content .= $content;
          \Drupal::logger('cms_migrations')->debug('Adding content to heading "@heading", total length now: @length', [
            '@heading' => $current_heading,
            '@length' => strlen($current_content),
          ]);
        } else {
          \Drupal::logger('cms_migrations')->warning('Found content without heading at index @index', [
            '@index' => $index,
          ]);
        }
      }
    }

    // Don't forget the last pair
    if ($current_heading && $current_content) {
      \Drupal::logger('cms_migrations')->info('Saving final section: heading=@heading, content_length=@length', [
        '@heading' => $current_heading,
        '@length' => strlen($current_content),
      ]);
      $create_sections[] = $this->createSectionParagraph($current_heading, $current_content);
    }

    \Drupal::logger('cms_migrations')->debug('Processed @count create sections from articles array', [
      '@count' => count($create_sections),
    ]);

    // Debug the sections data
    foreach ($create_sections as $index => $section) {
      \Drupal::logger('cms_migrations')->debug('Section @index: type=@type, category=@category, content=@content', [
        '@index' => $index,
        '@type' => $section['type'] ?? 'unknown',
        '@category' => $section['field_category_selection'] ?? 'unknown',
        '@content' => substr($section['field_content'] ?? 'unknown', 0, 100),
      ]);
    }

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
    $section_data = [
      'type' => 'create_section',
      'field_category_selection' => $heading,
      'field_content' => trim($content),
    ];

    \Drupal::logger('cms_migrations')->debug('Creating section paragraph: category=@category, content=@content', [
      '@category' => $heading,
      '@content' => substr(trim($content), 0, 100),
    ]);

    return $section_data;
  }

  /**
   * Ensure that a category value exists in the field_category_selection allowed values.
   *
   * @param string $value
   *   The category value to ensure exists.
   */
  protected function ensureCategoryValueExists($value) {
    try {
      // Get the field storage configuration
      $field_storage = \Drupal::entityTypeManager()
        ->getStorage('field_storage_config')
        ->load('paragraph.field_category_selection');

      if (!$field_storage) {
        \Drupal::logger('cms_migrations')->warning('Field storage for field_category_selection not found');
        return;
      }

      // Get current allowed values
      $settings = $field_storage->getSettings();
      $allowed_values = $settings['allowed_values'] ?? [];

      \Drupal::logger('cms_migrations')->debug('Current allowed values: @values', [
        '@values' => implode(', ', array_keys($allowed_values)),
      ]);

      // Check if the value already exists
      if (isset($allowed_values[$value])) {
        \Drupal::logger('cms_migrations')->debug('Category value @value already exists', [
          '@value' => $value,
        ]);
        return;
      }

      // Add the new value
      $allowed_values[$value] = $value;
      $settings['allowed_values'] = $allowed_values;
      $field_storage->setSettings($settings);
      $field_storage->save();

      \Drupal::logger('cms_migrations')->info('Added category value @value to field_category_selection', [
        '@value' => $value,
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('cms_migrations')->error('Failed to ensure category value exists: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
