<?php

declare(strict_types=1);

namespace Drupal\cms_migrations\Drush\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands for CMS Migrations.
 */
class CmsMigrationCommands extends DrushCommands {

  /**
   * Test command to verify Drush registration.
   *
   * @command cms-migrations:test
   * @aliases cms-test
   * @usage cms-migrations:test
   *   Test command to verify service registration.
   */
  public function testCommand() {
    $this->output()->writeln("CMS Migrations commands are working!");
    return;
  }

  /**
   * List available projects for migration.
   *
   * @command cms-migrations:list-projects
   * @aliases cms-list-projects
   * @usage cms-migrations:list-projects
   *   List all available projects in the cms-source directory.
   */
  public function listProjects() {
    $module_path = \Drupal::service('extension.list.module')->getPath('cms_migrations');
    $source_directory = $module_path . '/cms-source';

    if (!is_dir($source_directory)) {
      $this->logger()->error('Source directory not found: ' . $source_directory);
      return;
    }

    $projects = [];
    $directories = glob($source_directory . '/*', GLOB_ONLYDIR);

    foreach ($directories as $directory) {
      $project_name = basename($directory);
      $projects[] = $project_name;
    }

    if (empty($projects)) {
      $this->output()->writeln('No projects found in cms-source directory.');
      return;
    }

    $this->output()->writeln('Available projects:');
    foreach ($projects as $project) {
      $this->output()->writeln('  - ' . $project);
    }
  }

}
