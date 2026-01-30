<?php

declare(strict_types=1);

namespace Drupal\cms_migrations\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;

/**
 * Drush commands for updating supplementary topics entity references.
 */
class SupplementaryTopicsUpdateCommands extends DrushCommands {

  /**
   * Update supplementary topics entity references after migration.
   *
   * @command cms-migrations:update-supplementary-topics
   * @aliases cms-update-topics
   * @usage cms-migrations:update-supplementary-topics
   *   Update supplementary topics references (will prompt for project selection).
   */
  public function updateSupplementaryTopics() {
    // Get available projects
    $available_projects = $this->getAvailableProjects();
    
    if (empty($available_projects)) {
      $this->output()->writeln('<error>No projects found in cms-source directory.</error>');
      return;
    }
    
    // Ask user to select project
    $project = $this->io()->choice('Select project to update supplementary topics for:', $available_projects);
    
    if (!$project) {
      $this->output()->writeln('<error>No project selected.</error>');
      return;
    }
    $this->output()->writeln("Updating supplementary topics entity references for project: {$project}");
    $this->output()->writeln("");

    try {
      // Get all fs_article nodes for this project
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'fs_article')
        ->condition('status', 1)
        ->accessCheck(FALSE);


      // Filter by project if we have project field
      if ($project) {
        // We'll need to join with taxonomy terms to filter by project
        // For now, let's get all fs_article nodes and filter later
      }

      $nids = $query->execute();

      if (empty($nids)) {
        $this->logger()->warning('No fs_article nodes found for project @project', [
          '@project' => $project,
        ]);
        return;
      }

      $this->output()->writeln("Found " . count($nids) . " fs_article nodes to process.");
      $this->output()->writeln("");
      $updated_count = 0;
      $processed_count = 0;

      foreach ($nids as $nid) {
        $processed_count++;
        
        $updated = $this->updateNodeSupplementaryTopics($nid);
        if ($updated) {
          $updated_count++;
        }

        // Show progress every 10 nodes
        if ($processed_count % 10 == 0) {
          $this->output()->writeln("Processed {$processed_count}/{" . count($nids) . "} nodes...");
        }
      }

      $this->output()->writeln("");
      $this->output()->writeln("Processing complete!");
      $this->output()->writeln("- Total nodes processed: {$processed_count}");
      $this->output()->writeln("- Nodes updated: {$updated_count}");
      $this->output()->writeln("- Nodes skipped: " . ($processed_count - $updated_count));

    } catch (\Exception $e) {
      $this->logger()->error('Error updating supplementary topics: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Update supplementary topics for a single node.
   *
   * @param int $nid
   *   The node ID to update.
   *
   * @return bool
   *   TRUE if the node was updated, FALSE otherwise.
   */
  protected function updateNodeSupplementaryTopics($nid) {
    try {
      $node = Node::load($nid);

      if (!$node) {
        dd('node not found');
        return FALSE;
      }
      // Get the _id from the node (field_source_id contains the _id from JSON)
      if (!$node->hasField('field_source_id') || $node->get('field_source_id')->isEmpty()) {
        return FALSE;
      }

      $node_id_value = $node->get('field_source_id')->value;
      if (empty($node_id_value)) {
        return FALSE;
      }

      // Find the original JSON data and extract supplementary topics
      $supplementary_topics = $this->extractSupplementaryTopicsFromSource($node_id_value);

      if (empty($supplementary_topics)) {
        return FALSE;
      }

      $referenced_nids = [];


      // Process each supplementary topic
      foreach ($supplementary_topics as $topic) {
        if (isset($topic['fs_id']) && !empty($topic['fs_id'])) {
          $referenced_nid = $this->findNodeByFsId($topic['fs_id']);
          if ($referenced_nid) {
            $referenced_nids[] = $referenced_nid;
          }
        }
      }

      // Update the field_supplementary_topics field
      if (!empty($referenced_nids)) {
        $node->set('field_supplementary_topics', $referenced_nids);
        $node->save();
        
        return TRUE;
      }

    } catch (\Exception $e) {
      $this->logger()->error('Error updating node @nid: @message', [
        '@nid' => $nid,
        '@message' => $e->getMessage(),
      ]);
    }

    return FALSE;
  }

  /**
   * Find a node by its _id field value.
   *
   * @param string $_id
   *   The _id to search for.
   *
   * @return int|null
   *   The node ID if found, NULL otherwise.
   */
  protected function findNodeByFsId($_id) {
    try {
      // Search by field_source_id (which stores the _id from JSON)
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'fs_article')
        ->condition('field_source_id', $_id)
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->range(0, 1);

      $nids = $query->execute();

      if (!empty($nids)) {
        return reset($nids);
      }

    } catch (\Exception $e) {
      $this->logger()->error('Error searching for _id @_id: @message', [
        '@_id' => $_id,
        '@message' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Extract supplementary topics from original JSON source data.
   *
   * @param string $node_id
   *   The _id to find in JSON files.
   *
   * @return array
   *   Array of supplementary topics with title and _id.
   */
  protected function extractSupplementaryTopicsFromSource($node_id) {
    $module_path = \Drupal::service('extension.list.module')->getPath('cms_migrations');
    $source_directory = $module_path . '/cms-source';

    // Search through all JSON files to find the _id
    $json_files = glob($source_directory . '/*/*.json');
    
    foreach ($json_files as $file) {
      $json_content = file_get_contents($file);
      if ($json_content === FALSE) {
        continue;
      }
      
      $json_data = json_decode($json_content, TRUE);
      if (!$json_data || !is_array($json_data)) {
        continue;
      }
      
      // Check if the JSON has the expected structure
      if (!isset($json_data['_embedded']['rh:doc'])) {
        continue;
      }

      // Search for the _id in the rh:doc array
      foreach ($json_data['_embedded']['rh:doc'] as $index => $item) {
        if (isset($item['fs_id'])) {
          if ($item['fs_id'] == $node_id) {
            // Found the item, now extract supplementary topics from articles array
            if (isset($item['articles']) && is_array($item['articles'])) {
              return $this->processArticlesForSupplementaryTopics($item['articles']);
            }
          }
        }
      }
    }

    return [];
  }

  /**
   * Process articles array to extract supplementary topics.
   *
   * @param array $articles
   *   The articles array from JSON.
   *
   * @return array
   *   Array of supplementary topics with title and _id.
   */
  protected function processArticlesForSupplementaryTopics($articles) {
    $supplementary_topics = [];
    $current_title = NULL;

    foreach ($articles as $article) {
      if (isset($article['supplementarytopicstitle'])) {
        $current_title = $article['supplementarytopicstitle'];
      }
      elseif (isset($article['supplementarytopicslink']) && $current_title) {
        $supplementary_topics[] = [
          'title' => $current_title,
          'fs_id' => $article['supplementarytopicslink'],
        ];
        $current_title = NULL; // Reset for next pair
      }
    }

    return $supplementary_topics;
  }

  /**
   * Get available projects from cms-source directory.
   *
   * @return array
   *   Array of available project names.
   */
  protected function getAvailableProjects() {
    $module_path = \Drupal::service('extension.list.module')->getPath('cms_migrations');
    $source_directory = $module_path . '/cms-source';

    if (!is_dir($source_directory)) {
      return [];
    }

    $projects = [];
    $directories = glob($source_directory . '/*', GLOB_ONLYDIR);

    foreach ($directories as $directory) {
      $project_name = basename($directory);
      $projects[] = $project_name;
    }

    return $projects;
  }

}
