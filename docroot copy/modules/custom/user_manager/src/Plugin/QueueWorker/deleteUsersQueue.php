<?php

namespace Drupal\user_manager\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Custom Queue Worker.
 *
 * @QueueWorker(
 *   id = "delete_Users_Queue",
 *   title = @Translation("Delete Users Queue"),
 * )
 */
class deleteUsersQueue extends QueueWorkerBase  {

/*  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // TODO: Implement create() method.
  }*/

  public function processItem($item) {
    $query = \Drupal::database();
    $data = $item->nid;

    $query->delete('UserDetails')
      ->condition('id', $data, '=')
      ->execute();

    $query->delete('addressDetails')
      ->condition('user_id', $data, '=')
      ->execute();

    $query->delete('departmentDetails')
      ->condition('user_id', $data, '=')
      ->execute();
  }
}
