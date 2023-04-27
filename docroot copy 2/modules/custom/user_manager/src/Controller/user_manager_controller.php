<?php

namespace Drupal\user_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Main controller where every data is displayed
 */
class user_manager_controller extends ControllerBase {

  /**
   * Functions to get users' data based on filter and display , also a button for adding more data
   * @return array
   */
  public function getUsers() {

    $form['filter'] = $this->formBuilder()->getForm('Drupal\user_manager\Form\filterUsers');

    $form['display'] = $this->formBuilder()->getForm('Drupal\user_manager\Form\displayUsers');

    $add_user_url = Url::fromRoute('user_manager.addUserDetails');

    $build['link'] = [
      '#type' => 'link',
      '#title' => 'ADD USER',
      '#url' => $add_user_url,
      '#attributes' => ['class' => 'button button--action button--primary'],

    ];

    $build['pager'] = [
      '#type' => 'pager'
    ];

    return [
      $build['link'],
      $form,
      $build['pager'],
      '#title' => 'User Details'
    ];
  }

}
