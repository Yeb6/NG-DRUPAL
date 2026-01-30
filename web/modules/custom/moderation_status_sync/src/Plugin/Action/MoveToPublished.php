<?php
namespace Drupal\moderation_status_sync\Plugin\Action;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\workflows\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
 
/**
* Moves node to 'published' state via workflow transition.
*
* @Action(
*   id = "move_to_published",
*   label = @Translation("Publish Content (Force with Transition)"),
*   type = "node"
* )
*/
class MoveToPublished extends ActionBase implements ContainerFactoryPluginInterface {
 
  /**
   * The workflow manager.
   *
   * @var \Drupal\workflows\WorkflowManagerInterface
   */
  protected $workflowManager;
 
  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WorkflowManagerInterface $workflow_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->workflowManager = $workflow_manager;
  }
 
  /**
   * Dependency injection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.workflow')
    );
  }
 
  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof NodeInterface && $entity->hasField('moderation_state')) {
      $workflow = $this->workflowManager->getWorkflowForEntity($entity);
      $current_state = $entity->get('moderation_state')->value;
 
      // Check if a transition to 'published' exists from current state.
      $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($current_state, 'published');
 
      if ($transition) {
        $entity->set('moderation_state', 'published');
        $entity->setPublished(TRUE);
        $entity->save();
 
        \Drupal::logger('moderation_status_sync')->notice('Node @title transitioned from @from to published.', [
          '@title' => $entity->label(),
          '@from' => $current_state,
        ]);
      }
      else {
        \Drupal::logger('moderation_status_sync')->warning('Node @title could not be published from @from — transition not allowed.', [
          '@title' => $entity->label(),
          '@from' => $current_state,
        ]);
      }
    }
  }
 
  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}