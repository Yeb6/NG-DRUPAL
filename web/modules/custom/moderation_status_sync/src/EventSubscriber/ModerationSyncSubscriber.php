<?php

namespace Drupal\moderation_status_sync\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class ModerationSyncSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }


	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents(): array {
		// Use the correct event class for entity presave in Drupal 10.
		return [
			EntityHookEvents::ENTITY_INSERT => 'entityInsert',
			EntityHookEvents::ENTITY_UPDATE => 'entityUpdate',
		];

	}

  /**
   * Updates field_moderation_status to match moderation_state.
   */
  public function entityInsert(EntityInsertEvent $event) {
    $node = $event->getEntity();

    // Only act on nodes of a specific content type.
    if ($node instanceof NodeInterface) {

      if ($node->hasField('moderation_state') && $node->hasField('field_moderation_status')) {
        $moderation_state = $node->get('moderation_state')->value ?? '';

        // Only update if the status has changed or is empty.
        if ($node->get('field_moderation_status')->value !== $moderation_state) {
          $node->set('field_moderation_status', $moderation_state);
					$node->save();
        }
      }
    }
  }

	/**
   * Updates field_moderation_status to match moderation_state.
   */
  public function entityUpdate(EntityUpdateEvent $event) {
    $node = $event->getEntity();

    // Only act on nodes of a specific content type.
    if ($node instanceof NodeInterface) {

      if ($node->hasField('moderation_state') && $node->hasField('field_moderation_status')) {
        $moderation_state = $node->get('moderation_state')->value ?? '';
        // Only update if the status has changed or is empty.
        if ($node->get('field_moderation_status')->value !== $moderation_state) {
          $node->set('field_moderation_status', $moderation_state);
					$node->save();
        }
      }
    }
  }
}
