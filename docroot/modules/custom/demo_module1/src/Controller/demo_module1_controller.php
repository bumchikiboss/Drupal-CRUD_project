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
    $database = \Drupal::database();
    $limit = 3;

    $query = $database->select("demo_UserDetails", 'ud');
    $query->fields('ud', ['id', 'username', 'email', 'gender']);
    $query->fields('ua', ['address']);
    $query->fields('dd',['department']);

    $query->join('demo_addressDetails', 'ua', 'ua.user_id=ud.id');
    $query->join('demo_departmentDetails', 'dd', 'dd.user_id=ud.id');
    $result = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit)->execute()->fetchAll();

    $data = [];

    foreach ($result as $row) {
      if($row->gender == 0) $gender = 'Male';
      elseif ($row->gender == 1) $gender = 'Female';
      else $gender = 'Others';

      $data[] = [
        'id' => $row->id,
        'username' => $row->username,
        'email' => $row->email,
        'gender' => $gender,
        'address' => $row->address,
        'department' => $row->department,
        'edit' => t("<a href='custom-editUsers/$row->id'>Edit</a>"),
        'delete' => t("<a href='custom-confirmDelete/$row->id'>Delete</a>")

      ];
    }

    $header = array('Id', 'Username', 'Email Id', 'Gender', 'Address', 'Department', 'Edit', 'Delete' );

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

  /*
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
*/
}

