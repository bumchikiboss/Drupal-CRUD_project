<?php

namespace Drupal\user_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class userPassQueueForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_pass_queue';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();
    $userData['user_id'] = $formValues['user_id'];
    $userData['password'] = $formValues['password'];

    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('user_pass_queue');
    $item = new \stdClass();
    $item->uid = $userData['user_id'];
    $item->pass = $userData['password'];
    $queue->createItem($item);

    $this->messenger()->addStatus($this->t('User Details has been saved.'));
    $form_state->setRedirect('user_manager.userPassQueue');
  }
}
