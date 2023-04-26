<?php

namespace Drupal\user_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\CronInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * fetch from database, display user data and perform multiple user operations
 */
class displayUsers extends FormBase {

  /**
   * @var CronInterface
   */
  protected $cron;

  /**
   * constructor.
   * @param CronInterface $cron
   */
  public function __construct(CronInterface $cron){
    $this->cron = $cron;
  }

  /**
   * @param ContainerInterface $container
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cron')
    );
  }

  public function getFormId() {
    return 'display_users';
  }

  /**
   * build 'tableselect' table form for displaying users
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $address = \Drupal::request()->query->get('address');
    $username = \Drupal::request()->query->get('username');

    $header = [
      'id' => [
        'data' => $this->t('Id'),
        'field' => 'ud.id',
        'sort' => 'desc',
      ],
      'username' => [
        'data' => $this->t('Username'),
        'field' => 'ud.username',
      ],
      'email' => [
        'data' => $this->t('Email ID'),
        'field' => 'ud.email',
      ],
      'gender' => [
        'data' => $this->t('Gender'),
        'field' => 'ud.gender',
      ],
      'address' => [
        'data' => $this->t('Address'),
        'field' => 'ua.address',
      ],
      'department' => [
        'data' => $this->t('Department'),
        'field' => 'dd.department',
      ],
      'edit' => $this->t('Edit'),
      'delete' => $this->t('Delete')
    ];

    /*$header = [
        'id' => [
          'data' => $this->t('Id'),
          'field' => 'ud.id',
          'sort' => 'desc',
        ],
        'username' => $this->t('Username'),
        'email' => $this->t('Email ID'),
        'gender' => $this->t('Gender'),
        'address' => $this->t('Address'),
        'department' => $this->t('Department'),
        'edit' => $this->t('Edit'),
        'delete' => $this->t('Delete')
    ];*/

    $database = \Drupal::database();
    $limit = 900;
    $query = $database->select("UserDetails", 'ud');
    $query->fields('ud', ['id', 'username', 'email', 'gender']);
    $query->fields('ua', ['address']);
    $query->fields('dd', ['department']);

    $query->join('addressDetails', 'ua', 'ua.user_id=ud.id');
    $query->join('departmentDetails', 'dd', 'dd.user_id=ud.id');
    if (!empty($address)) {
      $query->condition('ua.address', $address);
    }
    if (!empty($username)) {
      $query->condition('ud.username', $username);
    }
    //$query->orderBy('ud.id','desc');
    $query = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $result = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit)->execute()->fetchAll();

    $data = [];

    foreach ($result as $row) {
      if ($row->gender == 0) $gender = 'Male';
      elseif ($row->gender == 1) $gender = 'Female';
      else $gender = 'Others';

      $edit = Url::fromRoute('user_manager.editUsers', ['id' => $row->id]);
      $edit_link = Link::fromTextAndUrl(t('Edit'), $edit);
      $delete = Url::fromRoute('user_manager.confirmDelete', ['id' => $row->id]);
      $delete_link = Link::fromTextAndUrl(t('Delete'), $delete);

      $data[$row->id] = [
        'id' => $row->id,
        'username' => $row->username,
        'email' => $row->email,
        'gender' => $gender,
        'address' => $row->address,
        'department' => $row->department,
        'edit' => $edit_link,
        'delete' => $delete_link,
      ];

    }

    $form['cron_run']['actions'] = ['#type' => 'actions'];
    $form['cron_run']['actions']['sumbit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run cron'),
      '#submit' => [[$this, 'cronRun']],
    ];

    $form['select']['operation'] = [
      '#title' => 'Operation',
      '#type' => 'select',
      '#options' => ['del' => $this->t('Delete Selected'), 'del_queue' => $this->t('Delete via Queue'), 'op3' => $this->t('Option 3')],
      '#default_value' => 1,
    ];
    $form['select']['actions']['submit'] = [
      '#value' => $this->t('Apply selected operation'),
      '#type' => 'submit',
    ];

    $form['users'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $data,
      '#empty' => $this->t('No record found!'),
    );

    return $form;
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronRun(array &$form, FormStateInterface &$form_state) {

    $this->cron->run();
    $this->messenger()->addMessage($this->t('Cron ran successfully.'));
  }

  /**
   * validating form when submit
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * runs when form is submitted
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $value = $form_state->getValues()['operation'];
    $users = $form_state->getValues()['users'];
    $selected_users = array_filter($users);

    if ($value == 'del') {
      $batch = array(
        'title' => t('Deleting Users...'),
        'operations' => [],
        'init_message' => t('Deleting'),
        'progress_message' => t('Processed @current out of @total.'),
        'error_message' => t('An error occurred during processing'),
        'finished' => '\Drupal\user_manager\Form\displayUsers::delBatchFinish',
      );
      foreach ($selected_users as $uid) {
        $batch['operations'][] = ['\Drupal\user_manager\Form\displayUsers::delBatch', [$uid]];
      }
      batch_set($batch);
    } else if ($value == 'del_queue') {
      $queue_factory = \Drupal::service('queue');
      $queue = $queue_factory->get('delete_Users_Queue');
      foreach ($selected_users as $uid) {
        $item = new \stdClass();
        $item->nid = $uid;
        $queue->createItem($item);
      }
    }
  }


  /**
   * function called to delete the users in batches
   * @param $uid
   * @param $context
   * @return void
   */
  public static function delBatch($uid, &$context) {
    $query = \Drupal::database();

    $query->delete('UserDetails')
      ->condition('id', $uid, '=')
      ->execute();

    $query->delete('addressDetails')
      ->condition('user_id', $uid, '=')
      ->execute();

    $query->delete('departmentDetails')
      ->condition('user_id', $uid, '=')
      ->execute();

    $context['message'] = $uid . t(' is deleting.');
    $context['results'][] = $uid;
  }

  /**
   * call back function called after batch process is finished
   * @param $success
   * @param $results
   * @param $operations
   * @return void
   */
  public static function delBatchFinish($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One user delete.', '@count users deleted.'
      );
    } else {
      $message = t('Finished with an error.');
    }

    \Drupal::messenger()->addStatus($message);

  }
}
