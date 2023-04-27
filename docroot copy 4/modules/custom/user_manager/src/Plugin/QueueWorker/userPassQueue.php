<?php

namespace Drupal\user_manager\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Custom Queue Worker.
 *
 * @QueueWorker(
 *   id = "user_pass_queue",
 *   title = @Translation("user_pass_queue"),
 * )
 */
class userPassQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * main constructor
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * grab functionality from the container
   * @param ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @return userPassQueue|static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }
  public function processItem($item) {

    $uid = $item->uid;
    $pass = $item->pass;

    $user_storage = $this->entityTypeManager->getStorage('user');

// Load user by their user ID
    $user = $user_storage->load($uid);

// Set the new password
    $user->setPassword($pass);

// Save the user
    $user->save();


    /*$user = \Drupal\user\Entity\User::load($uid);
    $user->setPassword($pass);
    $user->save();*/
  }
}
