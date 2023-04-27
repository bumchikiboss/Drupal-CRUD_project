<?php

namespace Drupal\user_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\file\Entity\File;


/**
 * Provides a user_manager form to edit user details.
 */
class EditUserDetails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_users';
  }

  /**
   * build form for editing users' data
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $id = \Drupal:: routeMatch()->getParameter('id');
    $query = \Drupal::database();
    $data = $query->select('UserDetails', 'ud');
    $data->fields('ud', ['id', 'username', 'email', 'gender']);
    $data->fields('ua', ['address']);
    $data->fields('dd', ['term_id','department'])
      ->condition('ud.id', $id, '=');
    $data->join('addressDetails', 'ua', 'ua.user_id=ud.id');
    $data->join('departmentDetails', 'dd', 'dd.user_id=ud.id');

    $data = $data->execute()->fetchAll(\PDO::FETCH_OBJ);

//    $data2 = $query->select('_UserDetails','ud');
//    $data2->fields('ud', ['id', 'username', 'email', 'gender']);
//    $data2->fields('ua', ['address'])
//          ->condition('ud.id',$id,'=');
//    $data2->join('demo_addressDetails', 'ua', 'ua.user_id=ud.id');
//    $result = $data2->execute()->fetchAll(\PDO::FETCH_OBJ);


    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#maxlength' => 50,
      '#default_value' => $data[0]->username,

    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('User Email'),
      '#required' => TRUE,
      '#maxlength' => 80,
      '#default_value' => $data[0]->email,

    ];

    $form['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#required' => TRUE,
      '#options' => [t('Male'), t('Female'), t('Others')],
      '#default_value' => $data[0]->gender,

    ];

    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
      '#maxlength' => 200,
      '#default_value' => $data[0]->address,

    ];

    /*$form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 20,
      '#default_value' => $data[0]->department,
    ];*/

    $department_vid = 'Department';
    $options = [];

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($department_vid);
    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    $default_department = $data[0]->term_id;

    $form['department'] = [
      '#type' => 'select',
      '#title' => $this->t('Department'),
      '#options' => $options,
      '#default_value' => $default_department,
      '#empty_option' => $this->t('-select-'),
      '#description' => $this->t('Select Department'),
      '#required' => TRUE,
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

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => 'Profile Pic (PNG)',
      '#upload_location' => 'public://profiles',
      '#upload_validators' => [
        'file_validate_extensions' => ['png'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update '),
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

    if (!preg_match('/^[A-Za-z]{1}[A-Za-z0-9]{2,50}$/', $_username)) {
      $form_state->setErrorByName('username', $this->t('Enter valid username'));
    }

    if (!\Drupal::service('email.validator')->isValid($_email)) {
      $form_state->setErrorByName('email', $this->t('Enter valid email'));
    }

  }


  /**
   * when form is submitted updates the values in database
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id = \Drupal:: routeMatch()->getParameter('id');
    $con = Database::getConnection();

    $formValues = $form_state->getValues();


    $userData['username'] = $formValues['username'];
    $userData['email'] = $formValues['email'];
    $userData['gender'] = $formValues['gender'];

    $userAddress['address'] = $formValues['address'];

    $userDepartmentKey = $formValues['department'];
    $userDepartment['department'] = $form['department']['#options'][$userDepartmentKey];

    $department_vid = 'Department';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($department_vid);
    foreach ($terms as $term) {
      if ($userDepartment['department'] == $term->name) {
        $term_id = $term->tid;
      }
    }
    $userDepartment['term_id'] = $term_id;

    $userPic = $formValues['file'];

    if (!empty($userPic[0])) {
      $file = File::load($userPic[0]);
      $file->setPermanent();
      $file->save();
      $userData['file'] = $file->getFileUri();
    }

    $oldFile = $con->select('UserDetails', 'ud')
      ->fields('ud', ['file'])
      ->condition('id', $id)
      ->execute()->fetchField();

    if ($oldFile) {
//      $fileOld = File::load($oldFile);
//      unlink($fileOld);
      unlink($oldFile);
    }

    $con->update('UserDetails')
      ->fields($userData)
      ->condition('id', $id)
      ->execute();

    $con->update('addressDetails')
      ->fields($userAddress)
      ->condition('user_id', $id)
      ->execute();

    $con->update('departmentDetails')
      ->fields($userDepartment)
      ->condition('user_id', $id)
      ->execute();

    $this->messenger()->addStatus($this->t('User Details has been updated.'));
    $form_state->setRedirect('user_manager.getUsers');
  }

}
