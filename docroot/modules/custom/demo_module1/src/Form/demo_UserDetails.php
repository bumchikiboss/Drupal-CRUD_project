<?php

namespace Drupal\demo_module1\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\file\Entity\File;


/**
 * Provides a Demo Module 1 form.
 */
class demo_UserDetails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_module1_demo__user_details';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#maxlength' => 50,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('User Email'),
      '#required' => TRUE,
      '#maxlength' => 80,
    ];

    $form['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#required' => TRUE,
      '#options' => [t('Male'),t('Female'),t('Others')],
    ];

    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
      '#maxlength' => 200,
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 20,
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => 'Profile Pic (PNG)',
      '#upload_location' => 'public://profiles',
      '#upload_validators' => [
        'file_validate_extensions' => ['png'],
        ],
    ];

    /*$form['skills'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Skills'),
      '#options' => [
        'C' => t('C'),
        'PHP' => t('PHP'),
        'Java' => t('Java')
      ],
    ];*/

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*if (mb_strlen($form_state->getValue('message')) < 10) {
      $form_state->setErrorByName('message', $this->t('Message should be at least 10 characters.'));
    }*/


    $formValues = $form_state->getValues();

    $_username = trim($formValues['username']);
    $_email = trim($formValues['email']);

    if (!preg_match('/^[A-Za-z]{1}[A-Za-z0-9]{2,50}$/', $_username))
    {
      $form_state->setErrorByName('username',$this->t('Enter valid username'));
    }

    if (!\Drupal::service('email.validator')->isValid($_email))
    {
      $form_state->setErrorByName('email',$this->t('Enter valid email'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $con = Database::getConnection();

    $formValues = $form_state->getValues();


    $userData['username'] = $formValues['username'];
    $userData['email'] = $formValues['email'];
    $userData['gender'] = $formValues['gender'];

    $userPic = $formValues['file'];

    if (isset($userPic[0]) && !empty($userPic[0])) {
      $file = File::load($userPic[0]);
      $file->setPermanent();
      $file->save();
    }

    $userID = $con->insert('demo_UserDetails')
        ->fields($userData)->execute();

    $userAddress['address'] = $formValues['address'];
    $userAddress['user_id'] = $userID;
    $con->insert('demo_addressDetails')
        ->fields($userAddress)->execute();

    $userDepartment['department'] = $formValues['department'];
    $userDepartment['user_id'] = $userID;
    $con->insert('demo_departmentDetails')
        ->fields($userDepartment)->execute();

    $this->messenger()->addStatus($this->t('User Details has been saved.'));
    $form_state->setRedirect('welcome_module.getUsers');
  }

}
