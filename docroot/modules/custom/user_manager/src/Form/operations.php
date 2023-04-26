<?php


namespace Drupal\user_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing;

class operations extends FormBase {
  public function getFormId() {
    return 'display_users';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select']['operation'] = [
      '#title' => 'Operation',
      '#type' => 'select',
      '#options' => [1 => $this->t('Delete Selected'), 2 => $this->t('Option 2'), 3 => $this->t('Option 3')],
      '#default_value' => 1,
    ];
    $form['select']['actions']['submit'] = [
      '#value' => $this->t('Apply selected operation'),
      '#type' => 'submit',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValues()['operation'];
    print_r($value);

  }
}
