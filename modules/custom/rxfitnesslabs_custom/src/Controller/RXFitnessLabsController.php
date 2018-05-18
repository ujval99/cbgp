<?php

namespace Drupal\rxfitnesslabs_custom\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

/**
 * Controller routines for page example routes.
 */
class RXFitnessLabsController extends ControllerBase {
  public function rxfitnesslabs($user_id) {
    $param = \Drupal::request()->query->all();
    $account = \Drupal\user\Entity\User::load($user_id);

    $query = db_query("SELECT DISTINCT msg_owner_id, msg_recipient_id FROM message WHERE msg_owner_id = ".$user_id." OR msg_recipient_id = ".$user_id." ORDER BY msg_created DESC");
    $records = $query->fetchAll();
    $a = array();
    foreach ($records as $value) {

      if($value->msg_owner_id == $user_id) {
        unset($value->msg_owner_id);
      }
      if($value->msg_recipient_id == $user_id) {
        unset($value->msg_recipient_id);
      }

      $array = (array) $value;
      $array = array_values($array);
      if (!in_array($array[0], $a)) {
        $a[] = $array[0];
        if (isset($param['sender_id'])) {
          if(isset($value->msg_recipient_id) && $value->msg_recipient_id == $param['sender_id']){
            $query = db_query("SELECT *
                 FROM message
                 WHERE (msg_recipient_id = ".$user_id." OR msg_owner_id = ".$user_id.") AND (msg_recipient_id = ".$value->msg_recipient_id." OR msg_owner_id = ".$value->msg_recipient_id.") ORDER BY msg_created DESC");
            $new[] = $query->fetchAll();
          }
          if(isset($value->msg_owner_id) && $value->msg_owner_id == $param['sender_id']){
            $query = db_query("SELECT *
                 FROM message
                 WHERE (msg_recipient_id = ".$user_id." OR msg_owner_id = ".$user_id.") AND (msg_recipient_id = ".$value->msg_owner_id." OR msg_owner_id = ".$value->msg_owner_id.") ORDER BY msg_created DESC");
            $new[] = $query->fetchAll();
          }
        }else{
          if(isset($value->msg_recipient_id)){
            $query = db_query("SELECT *
                 FROM message
                 WHERE (msg_recipient_id = ".$user_id." OR msg_owner_id = ".$user_id.") AND (msg_recipient_id = ".$value->msg_recipient_id." OR msg_owner_id = ".$value->msg_recipient_id.") ORDER BY msg_created DESC LIMIT 1");
            $new[] = $query->fetchAll();
          }
          if(isset($value->msg_owner_id)){
            $query = db_query("SELECT *
                 FROM message
                 WHERE (msg_recipient_id = ".$user_id." OR msg_owner_id = ".$user_id.") AND (msg_recipient_id = ".$value->msg_owner_id." OR msg_owner_id = ".$value->msg_owner_id.") ORDER BY msg_created DESC LIMIT 1");
            $new[] = $query->fetchAll();
          }
        }
      }
      
    }
   
    if (isset($new)) {
    $postArr = array_map('array_filter', $new);
    $postArr = array_filter( $postArr );
    $postArr = array_map("unserialize", array_unique(array_map("serialize", $postArr)));

    if (isset($param['sender_id'])) {
      foreach ($postArr[0] as $key => $value) {
        if (isset($value->msg_recipient_picture_id)) {
          $file = \Drupal\file\Entity\File::load($value->msg_recipient_picture_id);
          $path = $file->getFileUri();
          $url = file_create_url($path);
          // $url = \Drupal\image\Entity\ImageStyle::load('medium')->buildUrl($file->getFileUri());
          $value->msg_recipient_picture_id = $url;
        }
        if (isset($value->msg_owner_picture_id)) {
          $file = \Drupal\file\Entity\File::load($value->msg_owner_picture_id);
          $path = $file->getFileUri();
          $url = file_create_url($path);
          // $url = \Drupal\image\Entity\ImageStyle::load('medium')->buildUrl($file->getFileUri());
          $value->msg_owner_picture_id = $url;
        }
      }
    }else{
      foreach ($postArr as $key => $value) {
        
        if($user_id == $value[0]->msg_owner_id){
          $value[0]->end_user = $value[0]->msg_recipient_id;
        }else{
          $value[0]->end_user = $value[0]->msg_owner_id;
          $value[0]->msg_recipient_id = $value[0]->msg_owner_id;
          $value[0]->msg_recipient_name = $value[0]->msg_owner_name;
          $value[0]->msg_recipient_picture_id = $value[0]->msg_owner_picture_id;
        }
        if (isset($value[0]->msg_recipient_picture_id)) {
          $file = \Drupal\file\Entity\File::load($value[0]->msg_recipient_picture_id);
          $path = $file->getFileUri();
          $url = file_create_url($path);
          // $url = \Drupal\image\Entity\ImageStyle::load('medium')->buildUrl($file->getFileUri());
          $value[0]->msg_recipient_picture_id = $url;

        }
      }
    }
    } else {
        $postArr = 'NULL';
    }
    $response = new Response();
    $response->setContent(json_encode($postArr, JSON_FORCE_OBJECT));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  public function rxfitnesslabs_message_list() {

    $select_conversation_query = db_query("SELECT DISTINCT conversation_id FROM message");
    $select_conversation = $select_conversation_query->fetchAll();

    foreach ($select_conversation as $key => $con_id) {
      $last_msg_con_id_query = db_query("SELECT * FROM message WHERE conversation_id = ".$con_id->conversation_id." ORDER BY msg_created DESC LIMIT 1");
      $last_msg[] = $last_msg_con_id_query->fetchAll();

      $conversation_query = db_query("SELECT * FROM message WHERE conversation_id = ".$con_id->conversation_id." ORDER BY msg_created ASC");
      $conversation[] = $conversation_query->fetchAll();
    }

    return [
      '#theme' => 'message_dashboard',
      '#test_var' => $last_msg,
      '#conversation_var' => $conversation,
    ];
  }
}



