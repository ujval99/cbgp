<?php

namespace Drupal\send_message\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "send_message",
 *   label = @Translation("Send Message"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/message/send"
 *   }
 * )
 */
class SendMessageResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new CustomRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post($node_type, $data) {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    // if (!$this->currentUser->hasPermission('access content')) {
    //   throw new AccessDeniedHttpException();
    // }

    $msg_owner_id = $node_type['sender_id'][0]['value'];
    $msg_recipient_id = $node_type['receiver_id'][0]['value'];
    $msg = $node_type['msg'][0]['value'];

    $owner_query = db_query("SELECT ud.name, pid.user_picture_target_id FROM users_field_data ud LEFT JOIN user__user_picture pid ON ud.uid=pid.entity_id WHERE ud.uid = ".$msg_owner_id);
    $owner = $owner_query->fetchAssoc();

    $recipient_query = db_query("SELECT ud.name, pid.user_picture_target_id FROM users_field_data ud LEFT JOIN user__user_picture pid ON ud.uid=pid.entity_id WHERE ud.uid = ".$msg_recipient_id);
    $recipient = $recipient_query->fetchAssoc();

    $check_conversation_query = db_query("SELECT *
         FROM message
         WHERE (msg_recipient_id = ".$msg_owner_id." OR msg_owner_id = ".$msg_owner_id.") AND (msg_recipient_id = ".$msg_recipient_id." OR msg_owner_id = ".$msg_recipient_id.") ORDER BY msg_created DESC LIMIT 1");
    $check_conversation = $check_conversation_query->fetchAll();
    $check_conversation_count = count($check_conversation);

    if ($check_conversation_count == 0) {
      $check_conversation_query = db_query("SELECT MAX(conversation_id) FROM message");
      $check_conversation = $check_conversation_query->fetchAssoc();
      $max_conversation_id = $check_conversation['MAX(conversation_id)']+1;
    }else{
      $max_conversation_id = $check_conversation[0]->conversation_id;
    }

    //$conn = \Drupal\Core\Database\Database::getConnection();
    db_insert('message')->fields(
      array(
        'msg_owner_id' => $msg_owner_id,
        'msg_owner_name' => $owner['name'],
        'msg_owner_picture_id' => $owner['user_picture_target_id'],
        'msg_recipient_id' => $msg_recipient_id,
        'msg_recipient_name' => $recipient['name'],
        'msg_recipient_picture_id' => $recipient['user_picture_target_id'],
        'msg' => $msg,
        'msg_created' => \Drupal::time()->getCurrentTime(),
        'conversation_id' => $max_conversation_id,
      )
    )->execute();

    $msg_owner_id = $node_type['sender_id'][0]['value'];
    $msg_recipient_id = $node_type['receiver_id'][0]['value'];
    $msg = $node_type['msg'][0]['value'];

    $appid = $msg_owner_id;
    $playerid = $msg_recipient_id;
    $msg = $msg;

    //print_r($playerid); exit;
    $receiver_key = db_select('user__field_ws_chat_key', 'c')
    ->fields('c',array('field_ws_chat_key_value'))
    ->condition('entity_id', $playerid)
    ->execute()
    ->fetchAssoc();

    $content = array(
        "en" => $msg
        );
    //print_r($receiver_key); exit;
    $fields = array(
        'app_id' => "2d64d202-c34e-4fb9-805b-8583c7cae9c3",
        'include_player_ids' => array($receiver_key['field_ws_chat_key_value']),
        'data' => array("foo" => 'Receiver:'.$msg_recipient_id.' Sender:'.$msg_owner_id.' Message Text:'.$msg),
        'contents' => $content
    );
    
    $fields = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                               'Authorization: Basic OWJlNDgwZmYtN2Y5Ny00NzBjLTkyMTctMmU4MTRhNGRhZTYx'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    curl_close($ch);

    $return = json_encode($response);
    //print_r($return); exit;    
    //$return = 'done';
    return new JsonResponse($return);
  }

}
