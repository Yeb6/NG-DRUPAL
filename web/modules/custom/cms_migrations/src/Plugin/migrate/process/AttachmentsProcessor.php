<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to handle attachments data from articles array.
 *
 * @MigrateProcessPlugin(
 *   id = "attachments_processor"
 * )
 */
class AttachmentsProcessor extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If no value provided, return empty array
    if (empty($value) || !is_array($value)) {
      return [];
    }

    $attachments = [];

    foreach ($value as $article) {
      if (!is_array($article)) {
        continue;
      }

      // Process attachments if they exist
      if (isset($article['attachments']) && is_array($article['attachments'])) {
        $article_attachments = $this->processAttachments($article['attachments']);
        $attachments = array_merge($attachments, $article_attachments);
      }
    }

    \Drupal::logger('cms_migrations')->debug('Processed @count attachments', [
      '@count' => count($attachments),
    ]);

    return $attachments;
  }

  /**
   * Process attachments data.
   *
   * @param array $attachments
   *   Array of attachments.
   *
   * @return array
   *   Processed attachments data.
   */
  protected function processAttachments(array $attachments) {
    $processed_attachments = [];

    foreach ($attachments as $attachment) {
      if (is_array($attachment)) {
        $processed_attachment = [
          'filename' => $attachment['filename'] ?? '',
          'url' => $attachment['url'] ?? '',
          'type' => $attachment['type'] ?? 'file',
          'size' => $attachment['size'] ?? 0,
          'description' => $attachment['description'] ?? '',
          'mime_type' => $attachment['mime_type'] ?? '',
        ];

        // Add any additional fields that might be present
        foreach ($attachment as $key => $value) {
          if (!isset($processed_attachment[$key])) {
            $processed_attachment[$key] = is_array($value) ? $value : (string) $value;
          }
        }

        $processed_attachments[] = $processed_attachment;
      }
    }

    return $processed_attachments;
  }

}
