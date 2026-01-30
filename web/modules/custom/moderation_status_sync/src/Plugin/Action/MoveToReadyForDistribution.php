<?php
namespace Drupal\moderation_status_sync\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
* Moves node to 'ready_for_distribution' state.
*
* @Action(
*   id = "move_to_ready_for_distribution",
*   label = @Translation("Move to Ready for Distribution"),
*   type = "node"
* )
*/
class MoveToReadyForDistribution extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof NodeInterface) {
      $entity->set('moderation_state', 'ready_for_distribution');
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
 