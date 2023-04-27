<?php

namespace Drupal\user_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing;

/**
 * Form to set filters in displaying data
 */
class filterUsers extends FormBase {
  public function getFormId() {
    return 'filter_users';
  }

  /**
   * Builds a form for filters
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $address = \Drupal::request()->query->get('address');
    $username = \Drupal::request()->query->get('username');

    $form['form']['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter'),
      '#open' => true,
    ];
    $form['form']['filters']['address'] = [
      '#type' => 'radios',
      '#title' => 'Address',
      '#multiple' => TRUE,
      '#options' => [
        'delhi' => 'Delhi',
        'punjab' => 'Punjab',
        'goa' => 'Goa',
      ],
      '#default_value' => $address,
      '#description' => 'Select City',
    ];

    $form['form']['filters']['username'] = [
      '#type' => 'search',
      '#title' => 'Username',
      '#default_value' => $username,
    ];

    $form['form']['filters']['actions'] = [
      '#type' => 'actions',
    ];

    $form['form']['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    // $formResetUrl = Url::fromRoute('user_manager.getUsers')->setRouteParameters(['address'=>'' , 'username'=>'']);
    $user_list_url = Url::fromRoute('user_manager.getUsers');
    $form['form']['filters']['reset'] = [
      '#type' => 'link',
      '#title' => 'Reset',
      '#url' => $user_list_url,
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * validate form values before submit
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();

    if ($formValues['address'] == "" && $formValues['username'] == "") {
      $this->messenger()->addWarning(t('Please enter valid filter'));
    }

  }

  /**
   * sets parameters as filters which are selected
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();

    $address = $formValues['address'];
    $username = $formValues['username'];

    $url = Url::fromRoute('user_manager.getUsers')
      ->setRouteParameters(['address' => $address, 'username' => $username]);

    $form_state->setRedirectUrl($url);
  }
}
