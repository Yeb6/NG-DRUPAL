<?php
namespace Drupal\moderation_status_sync\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
/**
* Moves node to 'archived' state (Unpublish).
*
* @Action(
*   id = "move_to_archived",
*   label = @Translation("Unpublish Content (Move to Archived)"),
*   type = "node"
* )
*/
class MoveToArchived extends ActionBase {
  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof NodeInterface) {
      $entity->set('moderation_state', 'archived');
      $entity->setPublished(FALSE); // Set as unpublished
      $entity->save();
    }
  }
  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}
 
 