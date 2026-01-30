<?php

namespace Drupal\cms_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to handle articles array with supplementary topics, attachments, and create section.
 *
 * @MigrateProcessPlugin(
 *   id = "articles_array_processor"
 * )
 */
class ArticlesArrayProcessor extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If no value provided, return empty array
    if (empty($value) || !is_array($value)) {
      return [];
    }

    $processed_articles = [];

    foreach ($value as $article) {
      if (!is_array($article)) {
        continue;
      }

      $processed_article = [
        'supplementary_topics' => [],
        'attachments' => [],
        'create_section' => [],
      ];

      // Process supplementary topics
      if (isset($article['supplementary_topics']) && is_array($article['supplementary_topics'])) {
        $processed_article['supplementary_topics'] = $this->processSupplementaryTopics($article['supplementary_topics']);
      }

      // Process attachments
      if (isset($article['attachments']) && is_array($article['attachments'])) {
        $processed_article['attachments'] = $this->processAttachments($article['attachments']);
      }

      // Process create section
      if (isset($article['create_section']) && is_array($article['create_section'])) {
        $processed_article['create_section'] = $this->processCreateSection($article['create_section']);
      }

      // Add other article data
      $processed_article['article_data'] = $article;

      $processed_articles[] = $processed_article;
    }

    \Drupal::logger('cms_migrations')->debug('Processed @count articles with supplementary topics, attachments, and create section data', [
      '@count' => count($processed_articles),
    ]);

    return $processed_articles;
  }

  /**
   * Process supplementary topics.
   *
   * @param array $topics
   *   Array of supplementary topics.
   *
   * @return array
   *   Processed supplementary topics.
   */
  protected function processSupplementaryTopics(array $topics) {
    $processed_topics = [];

    foreach ($topics as $topic) {
      if (is_array($topic)) {
        $processed_topics[] = [
          'title' => $topic['title'] ?? '',
          'content' => $topic['content'] ?? '',
          'type' => $topic['type'] ?? 'supplementary',
        ];
      }
    }

    return $processed_topics;
  }

  /**
   * Process attachments.
   *
   * @param array $attachments
   *   Array of attachments.
   *
   * @return array
   *   Processed attachments.
   */
  protected function processAttachments(array $attachments) {
    $processed_attachments = [];

    foreach ($attachments as $attachment) {
      if (is_array($attachment)) {
        $processed_attachments[] = [
          'filename' => $attachment['filename'] ?? '',
          'url' => $attachment['url'] ?? '',
          'type' => $attachment['type'] ?? 'file',
          'size' => $attachment['size'] ?? 0,
        ];
      }
    }

    return $processed_attachments;
  }

  /**
   * Process create section data.
   *
   * @param array $create_section
   *   Array of create section data.
   *
   * @return array
   *   Processed create section data.
   */
  protected function processCreateSection(array $create_section) {
    $processed_section = [];

    foreach ($create_section as $key => $value) {
      if (is_array($value)) {
        $processed_section[$key] = $value;
      } else {
        $processed_section[$key] = (string) $value;
      }
    }

    return $processed_section;
  }

}
