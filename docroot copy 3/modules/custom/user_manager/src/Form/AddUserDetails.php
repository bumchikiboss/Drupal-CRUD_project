<?php

namespace Drupal\user_manager\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\file\Entity\File;



/**
 * Provides a user_manager form to enter user details.
 */
class AddUserDetails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_user_details';
  }

  /**
   * build form for entering users' data
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
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
      '#options' => [t('Male'), t('Female'), t('Others')],
    ];

    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
      '#maxlength' => 200,
    ];

    /*$form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 20,
    ];*/

    $department_vid = 'Department';
    $options = [];

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($department_vid);
    foreach ($terms as $term) {
      $options[] = $term->name;
    }

    $form['department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department'),
      '#options' => $options,
      '#empty_option' => $this->t('-select-'),
      '#description' => $this->t('Select Department'),
      '#required' => TRUE,
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
   * validating form when submit
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*if (mb_strlen($form_state->getValue('message')) < 10) {
      $form_state->setErrorByName('message', $this->t('Message should be at least 10 characters.'));
    }*/


    $formValues = $form_state->getValues();

    $_username = trim($formValues['username']);
    $_email = trim($formValues['email']);
    $_address = trim($formValues['address']);

    if (!preg_match('/^[A-Za-z]{1}[A-Za-z0-9]{2,50}$/', $_username)) {
      $form_state->setErrorByName('username', $this->t('Enter valid username'));
    }

    if (!preg_match('/^[A-Za-z]{1}[A-Za-z0-9]{2,200}$/', $_address)) {
      $form_state->setErrorByName('address', $this->t('Enter valid address'));
    }


    if (!\Drupal::service('email.validator')->isValid($_email)) {
      $form_state->setErrorByName('email', $this->t('Enter valid email'));
    }

  }

  /**
   * when form is submitted stores the values in database
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $con = Database::getConnection();

    $formValues = $form_state->getValues();


    $userData['username'] = $formValues['username'];
    $userData['email'] = $formValues['email'];
    $userData['gender'] = $formValues['gender'];

    $userPic = $formValues['file'];

    if (!empty($userPic[0])) {
      $file = File::load($userPic[0]);
      $file->setPermanent();
      $file->save();
      $userData['file'] = $file->getFileUri();
    }

    $userDepartmentKey = $formValues['department'];
    $userDepartment['department'] = $form['department']['#options'][$userDepartmentKey];

    $department_vid = 'Department';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($department_vid);
    foreach ($terms as $term) {
      if ($userDepartment['department'] == $term->name) {
        $term_id = $term->tid;
      }
    }

    //for($i =0;$i<900;$i++){
      $userID = $con->insert('UserDetails')
        ->fields($userData)->execute();


      $userAddress['address'] = $formValues['address'];
      $userAddress['user_id'] = $userID;
      $con->insert('addressDetails')
        ->fields($userAddress)->execute();


      $userDepartment['term_id'] = $term_id;
      $userDepartment['user_id'] = $userID;
      $con->insert('departmentDetails')
        ->fields($userDepartment)->execute();
    //}

    $this->messenger()->addStatus($this->t('User Details has been saved.'));
    $form_state->setRedirect('user_manager.getUsers');
  }

}
