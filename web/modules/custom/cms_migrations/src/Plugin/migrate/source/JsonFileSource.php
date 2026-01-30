<?php

namespace Drupal\cms_migrations\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Source plugin for reading JSON files from cms-source directory.
 *
 * @MigrateSource(
 *   id = "json_file_source"
 * )
 */
class JsonFileSource extends SourcePluginBase {

  /**
   * The JSON data.
   *
   * @var array
   */
  protected $jsonData = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->loadJsonData();
  }

  /**
   * Load JSON data from files in cms-source directory.
   */
  protected function loadJsonData() {
    $module_path = \Drupal::service('extension.list.module')->getPath('cms_migrations');
    $source_directory = $module_path . '/cms-source';

    if (!is_dir($source_directory)) {
      return;
    }

    // Get the project name from configuration
    $project_name = $this->configuration['project_name'] ?? NULL;
    $content_type = $this->configuration['content_type'] ?? 'fs_article';

    if (!$project_name) {
      return;
    }

    $project_directory = $source_directory . '/' . $project_name;

    if (!is_dir($project_directory)) {
      return;
    }

    $files = glob($project_directory . '/*.json');

    foreach ($files as $file) {
      if (is_file($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, TRUE);

        if (isset($data['_embedded']['rh:doc']) && is_array($data['_embedded']['rh:doc'])) {
          // Filter by templateName based on content type
          $filtered_docs = array_filter($data['_embedded']['rh:doc'], function($doc) use ($content_type) {
            $template_name = $doc['templateName'] ?? '';

            if ($content_type === 'fs_article') {
              // Include all article templates for fs_article content type
              return in_array($template_name, [
                'pt_red_article',
                'pt_green_article',
                'pt_chapter',
                'pt_start',
              ]);
            }

            return TRUE; // If no specific content type, include all
          });

          $this->jsonData = array_merge($this->jsonData, $filtered_docs);
          
          // Debug loaded data
          if (!empty($filtered_docs)) {
            $first_doc = reset($filtered_docs);
            \Drupal::logger('cms_migrations')->debug('Loaded @count documents for content type @type', [
              '@count' => count($filtered_docs),
              '@type' => $content_type,
            ]);
            
            if (isset($first_doc['articles'])) {
              \Drupal::logger('cms_migrations')->debug('First document has articles field with @count items', [
                '@count' => is_array($first_doc['articles']) ? count($first_doc['articles']) : 'not array',
              ]);
            } else {
              \Drupal::logger('cms_migrations')->debug('First document does not have articles field');
            }
            
            // Debug the actual document structure
            \Drupal::logger('cms_migrations')->debug('First document keys: @keys', [
              '@keys' => implode(', ', array_keys($first_doc)),
            ]);
          } else {
            \Drupal::logger('cms_migrations')->debug('No documents found for content type @type', [
              '@type' => $content_type,
            ]);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      '_id' => $this->t('ID'),
      'fs_project_name' => $this->t('FS Project Name'),
      'fs_project_id' => $this->t('FS Project ID'),
      'parentIentifier' => $this->t('Parent Identifier'),
      'hierarchy_level' => $this->t('Hierarchy Level'),
      'level_index' => $this->t('Level Index'),
      'headline' => $this->t('Headline'),
      'fs_id' => $this->t('FS ID'),
      'fs_language' => $this->t('FS Language'),
      'brands' => $this->t('Brands'),
      'parentIdentifier1' => $this->t('Parent Identifier 1'),
      'parentIdentifier2' => $this->t('Parent Identifier 2'),
      'parentIdentifier3' => $this->t('Parent Identifier 3'),
      'parentIdentifier4' => $this->t('Parent Identifier 4'),
      'parentIdentifier5' => $this->t('Parent Identifier 5'),
      'parentIdentifier6' => $this->t('Parent Identifier 6'),
      'parentIdentifier7' => $this->t('Parent Identifier 7'),
      'parentIdentifier8' => $this->t('Parent Identifier 8'),
      'parentIdentifier9' => $this->t('Parent Identifier 9'),
      'parentIdentifier10' => $this->t('Parent Identifier 10'),
      'release_status' => $this->t('Release Status'),
      'templateName' => $this->t('Template Name'),
      'articles' => $this->t('Articles'),
      'supplementary_topics_temp' => $this->t('Supplementary Topics Temporary'),
      'last_release_article' => $this->t('Last Release Article'),
      'LastReleaseDatetime' => $this->t('Release Date'),
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      '_id' => [
        'type' => 'string',
        'max_length' => 255,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator($this->jsonData);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    
    // Debug articles field
    $articles = $row->getSourceProperty('articles');
    if (!empty($articles)) {
      \Drupal::logger('cms_migrations')->debug('Source has articles data with @count items', [
        '@count' => is_array($articles) ? count($articles) : 'not array',
      ]);
    } else {
      \Drupal::logger('cms_migrations')->debug('Source has no articles data');
    }
    
    // Debug all available source properties
    $all_properties = $row->getSource();
    \Drupal::logger('cms_migrations')->debug('Available source properties: @properties', [
      '@properties' => implode(', ', array_keys($all_properties)),
    ]);
    
    // Convert LastReleaseDatetime to proper date format if needed
    $release_date = $row->getSourceProperty('LastReleaseDatetime');
    if ($release_date && is_numeric($release_date)) {
      // Convert timestamp to ISO date format (cast to int to avoid precision warning)
      $timestamp = (int) ($release_date / 1000);
      $row->setSourceProperty('Release Date', date('Y-m-d\TH:i:s', $timestamp));
    }

    // Keep articles array as-is for paragraph processing
    $articles = $row->getSourceProperty('articles');
    if (is_array($articles)) {
      \Drupal::logger('cms_migrations')->debug('Processing articles array with @count items', [
        '@count' => count($articles),
      ]);
      
      // Create a combined body content for news (but keep original articles array)
      $body_content = [];
      foreach ($articles as $article) {
        if (isset($article['content'])) {
          $body_content[] = $article['content'];
        }
      }
      $row->setSourceProperty('body_content', implode("\n\n", $body_content));
      
      \Drupal::logger('cms_migrations')->debug('Articles array preserved with @count items', [
        '@count' => count($articles),
      ]);
    } else {
      \Drupal::logger('cms_migrations')->debug('Articles field is not an array: @type', [
        '@type' => gettype($articles),
      ]);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'JSON File Source';
  }

}
