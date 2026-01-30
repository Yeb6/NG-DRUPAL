<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Process plugin to create or lookup project taxonomy terms.
 *
 * @MigrateProcessPlugin(
 *   id = "project_taxonomy_lookup"
 * )
 */
class ProjectTaxonomyLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get configuration
    $vocabulary = $this->configuration['vocabulary'] ?? 'projects';
    $source_field = $this->configuration['source_field'] ?? 'fs_project_name';

    // Get the source value
    $source_value = $row->getSourceProperty($source_field);
    
    if (empty($source_value)) {
      \Drupal::logger('cms_migrations')->warning('No source value found for field: @field', [
        '@field' => $source_field,
      ]);
      return NULL;
    }

    \Drupal::logger('cms_migrations')->debug('Looking up project taxonomy term for: @value', [
      '@value' => $source_value,
    ]);

    // Try to find existing term by project name
    $existing_term = $this->findExistingProjectTerm($source_value, $vocabulary);
    
    if ($existing_term) {
      \Drupal::logger('cms_migrations')->debug('Found existing project term: @id (@name)', [
        '@id' => $existing_term->id(),
        '@name' => $existing_term->getName(),
      ]);
      return $existing_term->id();
    }

    // Create new term if not found
    $new_term = $this->createNewProjectTerm($source_value, $vocabulary, $row);
    
    if ($new_term) {
      \Drupal::logger('cms_migrations')->info('Created new project term: @id (@name)', [
        '@id' => $new_term->id(),
        '@name' => $new_term->getName(),
      ]);
      return $new_term->id();
    }

    \Drupal::logger('cms_migrations')->error('Failed to create or find project taxonomy term for: @value', [
      '@value' => $source_value,
    ]);
    return NULL;
  }

  /**
   * Find existing project taxonomy term.
   *
   * @param string $project_name
   *   The project name to search for.
   * @param string $vocabulary
   *   The vocabulary machine name.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   The existing term or NULL if not found.
   */
  protected function findExistingProjectTerm($project_name, $vocabulary) {
    try {
      // Search by project name field first
      $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', $vocabulary)
        ->condition('field_project_name', $project_name)
        ->accessCheck(FALSE)
        ->range(0, 1);
      
      $tids = $query->execute();
      
      if (!empty($tids)) {
        $tid = reset($tids);
        return Term::load($tid);
      }

      // Fallback: search by term name
      $query = \Drupal::entityQuery('taxonomy_term')
        ->condition('vid', $vocabulary)
        ->condition('name', $project_name)
        ->accessCheck(FALSE)
        ->range(0, 1);
      
      $tids = $query->execute();
      
      if (!empty($tids)) {
        $tid = reset($tids);
        return Term::load($tid);
      }

    } catch (\Exception $e) {
      \Drupal::logger('cms_migrations')->error('Error searching for project term: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Create new project taxonomy term.
   *
   * @param string $project_name
   *   The project name.
   * @param string $vocabulary
   *   The vocabulary machine name.
   * @param \Drupal\migrate\Row $row
   *   The migration row for additional field data.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   The created term or NULL if creation failed.
   */
  protected function createNewProjectTerm($project_name, $vocabulary, Row $row) {
    try {
      // Check if vocabulary exists
      $vocabularies = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_vocabulary')
        ->loadByProperties(['vid' => $vocabulary]);

      if (empty($vocabularies)) {
        \Drupal::logger('cms_migrations')->error('Vocabulary @vocab does not exist', [
          '@vocab' => $vocabulary,
        ]);
        return NULL;
      }

      // Get additional field data from the row
      $fs_project_id = $row->getSourceProperty('fs_project_id');
      
      // Create the term
      $term = Term::create([
        'vid' => $vocabulary,
        'name' => $project_name, // Default title field
      ]);

      // Set custom fields if they exist
      if ($term->hasField('field_project_name')) {
        $term->set('field_project_name', $project_name);
      }

      if ($term->hasField('field_project_code') && !empty($fs_project_id)) {
        $term->set('field_project_code', $fs_project_id);
      }

      $term->save();

      \Drupal::logger('cms_migrations')->debug('Created project term with fields: name=@name, project_name=@project_name, project_code=@project_code', [
        '@name' => $project_name,
        '@project_name' => $project_name,
        '@project_code' => $fs_project_id ?? 'not set',
      ]);

      return $term;

    } catch (\Exception $e) {
      \Drupal::logger('cms_migrations')->error('Failed to create project taxonomy term: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
