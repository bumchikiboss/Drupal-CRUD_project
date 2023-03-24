<?php

namespace Drupal\demo_module1\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;


/**
 * Provides a Demo Module 1 form.
 */
class demo_ConfirmDelete extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_module1_demo__confirm_delete';
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state) {

    $id = \Drupal:: routeMatch()->getParameter('id');
    $query = \Drupal::database();
    $data = $query->select('demo_UserDetails','e')
      ->fields('e',['username'])
      ->condition('id',$id,'=')
      ->execute()->fetchAll(\PDO::FETCH_OBJ);


    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'USERNAME : ',
      '#maxlength' => 50,
      '#default_value' => $data[0]->username,
    ];

    return parent::buildForm($form,$form_state);
  }

  public function getQuestion()
  {
    return t('Do you want to delete this record?');
  }

  public function getCancelUrl()
  {
    return Url::fromRoute('welcome_module.getUsers');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id = \Drupal:: routeMatch()->getParameter('id');

    $query = \Drupal::Database();

    $oldFile = $query->select('demo_UserDetails','ud')
      ->fields('ud',['file'])
      ->condition('id',$id)
      ->execute()->fetchField();

    if($oldFile){
      unlink($oldFile);
    }

    $query->delete('demo_UserDetails')
          ->condition('id',$id,'=')
          ->execute();

    $query->delete('demo_addressDetails')
          ->condition('user_id',$id,'=')
          ->execute();

    $query->delete('demo_departmentDetails')
          ->condition('user_id',$id,'=')
          ->execute();

    $response = new \Symfony\Component\HttpFoundation\RedirectResponse('../custom-displayUsers');
    $response->send();

    $this->messenger()->addStatus($this->t('User Details has been Deleted.'));
  }

}
