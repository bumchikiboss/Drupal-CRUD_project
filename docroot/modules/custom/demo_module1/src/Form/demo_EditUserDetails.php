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
class demo_EditUserDetails extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_users';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $id = \Drupal:: routeMatch()->getParameter('id');
    $query = \Drupal::database();
    $data = $query->select('demo_UserDetails','ud');
    $data ->fields('ud',['id','username','email','gender']);
    $data ->fields('ua', ['address']);
    $data ->fields('dd', ['department'])
          ->condition('ud.id',$id,'=');
    $data->join('demo_addressDetails', 'ua', 'ua.user_id=ud.id');
    $data->join('demo_departmentDetails', 'dd', 'dd.user_id=ud.id');

    $data = $data->execute()->fetchAll(\PDO::FETCH_OBJ);

//    $data2 = $query->select('demo_UserDetails','ud');
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
      '#default_value'=> $data[0]->username,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('User Email'),
      '#required' => TRUE,
      '#maxlength' => 80,
      '#default_value'=> $data[0]->email,

    ];

    $form['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#required' => TRUE,
      '#options' => [t('Male'),t('Female'),t('Others')],
      '#default_value'=> $data[0]->gender,

    ];

    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
      '#maxlength' => 200,
      '#default_value'=> $data[0]->address,

    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 20,
      '#default_value'=> $data[0]->department,
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

    $id = \Drupal:: routeMatch()->getParameter('id');
    $con = Database::getConnection();

    $formValues = $form_state->getValues();


    $userData['username'] = $formValues['username'];
    $userData['email'] = $formValues['email'];
    $userData['gender'] = $formValues['gender'];

    $userAddress['address'] = $formValues['address'];

    $userDepartment['department'] = $formValues['department'];

    $userPic = $formValues['file'];

    if(!empty($userPic[0])){
      $file = File::load($userPic[0]);
      $file->setPermanent();
      $file->save();
      $userData['file'] = $file->getFileUri();
    }

    $oldFile = $con->select('demo_UserDetails','ud')
      ->fields('ud',['file'])
      ->condition('id',$id)
      ->execute()->fetchField();

    if($oldFile){
//      $fileOld = File::load($oldFile);
//      unlink($fileOld);
      unlink($oldFile);
    }

    $con->update('demo_UserDetails')
      ->fields($userData)
      ->condition('id',$id)
      ->execute();

    $con->update('demo_addressDetails')
      ->fields($userAddress)
      ->condition('user_id',$id)
      ->execute();

    $con->update('demo_departmentDetails')
      ->fields($userDepartment)
      ->condition('user_id',$id)
      ->execute();

    $this->messenger()->addStatus($this->t('User Details has been updated.'));
    $form_state->setRedirect('welcome_module.getUsers');
  }

}
