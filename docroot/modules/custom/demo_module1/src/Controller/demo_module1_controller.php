<?php

namespace Drupal\demo_module1\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Link;

class demo_module1_controller extends ControllerBase
{
  public function welcome()
  {
    return array(
      '#markup' => 'Welcome to my first Custom Module.'
    );
  }

  public function getUsers()
  {
    $query = \Drupal::database();
    $limit = 3;

    $result = $query->select("demo_UserDetails", 'ud');
    $result
      ->join('demo_addressDetails','ad','ud.id = ad.user_id');
    $result
      ->fields('ud', ['id', 'username', 'email', 'gender'])
      ->fields('ad',['address']);
    $result
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit)
      ->execute()->fetchAll(\PDO::FETCH_OBJ);

    $data = [];

    foreach ($result as $row) {
      $data[] = [
        'id' => $row->id,
        'username' => $row->username,
        'email' => $row->email,
        'gender' => $row->gender,
        'address' => $row->address,
        'edit' => t("<a href='custom-editUsers/$row->id'>Edit</a>"),
        'delete' => t("<a href='custom-confirmDelete/$row->id'>Delete</a>")

      ];
    }

    $header = array('Id', 'Username', 'Email Id', 'Gender', 'Address', 'Edit', 'Delete' );

    $add_user_url = Url::fromRoute('demo_module1.demo__user_details');

    $build['link'] = [
      '#markup' => 'ADD USER',
      '#type' => 'link',
      '#title' => 'ADD USER',
      '#url' => $add_user_url

    ];


    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $data
    ];

    $build['pager'] = [
      '#type' => 'pager'
    ];

    return [
      $build,
      '#title' => 'User Details'
    ];
  }

  public function deleteUsers($id)
  {
    $query = \Drupal::Database();
    $query->delete('demo_UserDetails')
          ->condition('id',$id,'=')
          ->execute();

    $response = new \Symfony\Component\HttpFoundation\RedirectResponse('../custom-displayUsers');
    $response->send();

    $this->messenger()->addStatus($this->t('User Details has been Deleted.'));
  }

}
