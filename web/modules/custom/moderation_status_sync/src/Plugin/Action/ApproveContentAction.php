<?php
namespace Drupal\moderation_status_sync\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Drupal\content_moderation\ModerationInformationInterface;

/**
 * Approves content by transitioning it to 'published' moderation state.
 *
 * @Action(
 *   id = "my_custom_approve_content",
 *   label = @Translation("Approve and publish content"),
 *   type = "node"
 * )
 */
class ApproveContentAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs the plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModerationInformationInterface $moderation_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moderationInfo = $moderation_info;
  }

  /**
   * Dependency injection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof NodeInterface && $this->moderationInfo->isModeratedEntity($entity)) {
      // Change moderation state to "published".
      $entity->set('moderation_state', 'published');

      // Optionally set node status to published as well.
      $entity->setPublished(TRUE);

      $entity->save();

      \Drupal::logger('moderation_status_sync')->info('Node @nid approved and published.', ['@nid' => $entity->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }
}
 