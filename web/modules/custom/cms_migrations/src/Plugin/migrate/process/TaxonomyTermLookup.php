<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Process plugin to create or lookup taxonomy terms.
 *
 * @MigrateProcessPlugin(
 *   id = "taxonomy_term_lookup"
 * )
 */
class TaxonomyTermLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get the vocabulary from configuration
    $vocabulary = $this->configuration['vocabulary'] ?? 'hierarchy';
    
    // Get the source field from configuration
    $source_field = $this->configuration['source_field'] ?? 'parentIentifier';
    
    // Get the actual value from the row
    $source_value = $row->getSourceProperty($source_field);
    
    // If no value provided, return NULL
    if (empty($source_value)) {
      \Drupal::logger('cms_migrations')->debug('No source value found for field: @field', ['@field' => $source_field]);
      return NULL;
    }

    // Clean the value
    $term_name = trim($source_value);
    
    \Drupal::logger('cms_migrations')->debug('Processing taxonomy term: @name in vocabulary: @vocab', [
      '@name' => $term_name,
      '@vocab' => $vocabulary,
    ]);
    
    // Try to find existing term first
    $existing_term = $this->findExistingTerm($term_name, $vocabulary);
    
    if ($existing_term) {
      \Drupal::logger('cms_migrations')->debug('Found existing term: @name (ID: @id)', [
        '@name' => $term_name,
        '@id' => $existing_term->id(),
      ]);
      return $existing_term->id();
    }

    // Create new term if it doesn't exist
    $term_id = $this->createNewTerm($term_name, $vocabulary, $row);
    if ($term_id) {
      \Drupal::logger('cms_migrations')->info('Created new taxonomy term: @name (ID: @id)', [
        '@name' => $term_name,
        '@id' => $term_id,
      ]);
    }
    return $term_id;
  }

  /**
   * Find existing taxonomy term by name.
   *
   * @param string $name
   *   The term name to search for.
   * @param string $vocabulary
   *   The vocabulary machine name.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   The existing term or NULL if not found.
   */
  protected function findExistingTerm($name, $vocabulary) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'name' => $name,
        'vid' => $vocabulary,
      ]);

    return !empty($terms) ? reset($terms) : NULL;
  }

  /**
   * Create a new taxonomy term.
   *
   * @param string $name
   *   The term name.
   * @param string $vocabulary
   *   The vocabulary machine name.
   * @param \Drupal\migrate\Row $row
   *   The migration row containing additional field data.
   *
   * @return int|null
   *   The term ID or NULL if creation failed.
   */
  protected function createNewTerm($name, $vocabulary, Row $row = NULL) {
    try {
      // Check if vocabulary exists
      $vocabularies = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_vocabulary')
        ->loadByProperties(['vid' => $vocabulary]);
      
      if (empty($vocabularies)) {
        \Drupal::logger('cms_migrations')->error('Vocabulary "@vocab" does not exist', ['@vocab' => $vocabulary]);
        return NULL;
      }
      
      $term_data = [
        'name' => $name,
        'vid' => $vocabulary,
      ];
      
      // Add additional fields if row data is available
      if ($row) {
        // Add level_index field
        $level_index = $row->getSourceProperty('level_index');
        if (!empty($level_index)) {
          $term_data['field_level_index'] = $level_index;
        }
        
        // Add parent identifier fields
        for ($i = 1; $i <= 10; $i++) {
          $field_name = 'parentIdentifier' . $i;
          $field_value = $row->getSourceProperty($field_name);
          if (!empty($field_value)) {
            $term_data['field_parentidentifier' . $i] = $field_value;
          }
        }
      }
      
      $term = Term::create($term_data);
      $term->save();
      
      \Drupal::logger('cms_migrations')->info('Successfully created taxonomy term: @name in vocabulary: @vocab', [
        '@name' => $name,
        '@vocab' => $vocabulary,
      ]);
      
      return $term->id();
    }
    catch (\Exception $e) {
      \Drupal::logger('cms_migrations')->error('Failed to create taxonomy term "@name" in vocabulary "@vocab": @message', [
        '@name' => $name,
        '@vocab' => $vocabulary,
        '@message' => $e->getMessage(),
      ]);
      
      return NULL;
    }
  }

}
