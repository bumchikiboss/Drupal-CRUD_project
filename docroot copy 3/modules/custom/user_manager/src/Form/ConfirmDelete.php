<?php

namespace Drupal\user_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;


/**
 * Provides user_manager form to confirm delete
 */
class ConfirmDelete extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_manager_confirm_delete';
  }

  /**
   * Builds a confirm form
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */

  public function buildForm(array $form, FormStateInterface $form_state) {

    return parent::buildForm($form, $form_state);
  }

  /**
   * Sets the question for the confirm form
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getQuestion() {
    $id = \Drupal:: routeMatch()->getParameter('id');
    $query = \Drupal::database();
    $data = $query->select('UserDetails', 'e')
      ->fields('e', ['username'])
      ->condition('id', $id, '=')
      ->execute()->fetchAll(\PDO::FETCH_OBJ);

    $username = $data[0]->username;

    return t('Do you want to delete ' . $username);
  }

  /**
   * Sets Url when clicked cancel
   * @return Url
   */
  public function getCancelUrl() {
    return Url::fromRoute('user_manager.getUsers');
  }

  /**
   * Deletes user details and uploaded file from the directory
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id = \Drupal:: routeMatch()->getParameter('id');

    $query = \Drupal::Database();

    $oldFile = $query->select('UserDetails', 'ud')
      ->fields('ud', ['file'])
      ->condition('id', $id)
      ->execute()->fetchField();

    if ($oldFile) {
      unlink($oldFile);
    }

    $query->delete('UserDetails')
      ->condition('id', $id, '=')
      ->execute();

    $query->delete('addressDetails')
      ->condition('user_id', $id, '=')
      ->execute();

    $query->delete('departmentDetails')
      ->condition('user_id', $id, '=')
      ->execute();

    $response = new \Symfony\Component\HttpFoundation\RedirectResponse('../displayUsers');
    $response->send();

    $this->messenger()->addStatus('User Deleted');
    
  }

}
