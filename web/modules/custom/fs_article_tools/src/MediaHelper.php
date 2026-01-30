<?php

namespace Drupal\fs_article_tools;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\node\NodeInterface;

/**
 * Provides helper methods for FS Article media.
 */
class MediaHelper {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new MediaHelper instance.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   */
  public function __construct(FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * Returns processed media items for a given fs_article node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The fs_article node entity.
   *
   * @return array
   *   An array of media items (images/documents).
   */
  public function getMediaItems(NodeInterface $node): array {
    $media_items = [];

    if ($node->bundle() !== 'fs_article') {
      return $media_items;
    }

    if (
      !$node->hasField('field_attachments')
      || $node->get('field_attachments')->isEmpty()
    ) {
      return $media_items;
    }

    foreach ($node->get('field_attachments') as $media_ref) {
      $media = $media_ref->entity;
      if (!$media instanceof Media) {
        continue;
      }

      $bundle = $media->bundle();

      // Process image media.
      if ($bundle === 'image' && $media->hasField('field_media_image')) {
        $image_file = $media->get('field_media_image')->entity;
        if ($image_file instanceof File) {
          $media_items[] = [
            'type' => 'image',
            'url' => $this->fileUrlGenerator->generateString(
              $image_file->getFileUri()
            ),
            'filename' => $image_file->getFilename(),
          ];
        }
      }
      elseif ($bundle === 'document' && $media->hasField('field_media_document')) {
        $doc_file = $media->get('field_media_document')->entity;
        if ($doc_file instanceof File) {
          $media_items[] = [
            'type' => 'document',
            'url' => $this->fileUrlGenerator->generateString(
              $doc_file->getFileUri()
            ),
            'filename' => $doc_file->getFilename(),
          ];
        }
      }
    }

    return $media_items;
  }

}
