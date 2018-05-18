<?php

namespace Drupal\cbgp_session\Controller;
use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for page example routes.
 */
class CBGP_SessionController extends ControllerBase {
	public function cbgp_session() {

        $appid = \Drupal::request()->query->get('senderid');
                $playerid = \Drupal::request()->query->get('receiverid');
                $msg = \Drupal::request()->query->get('message');
//             print $playerid; exit;
                $receiver_key = db_select('user__field_ws_chat_key', 'c')
            ->fields('c',array('field_ws_chat_key_value'))
            ->condition('entity_id', $playerid)
            ->execute()
            ->fetchAssoc();

                $content = array(
                        "en" => $msg
                        );

                $fields = array(
                        'app_id' => "2d64d202-c34e-4fb9-805b-8583c7cae9c3", //"bd0429dd-34c8-4948-9e9b-b640c303bfb8",
                        'include_player_ids' => array($receiver_key['field_ws_chat_key_value']),
                'data' => array("foo" => "bar"),
                        'contents' => $content
                );

                $fields = json_encode($fields);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                                                                                   'Authorization: Basic OWJlNDgwZmYtN2Y5Ny00NzBjLTkyMTctMmU4MTRhNGRhZTYx'));
			//MGRkNzg2MDEtMmVlMi00YWM4LTgzMzctYWU4OTQyMTZjMWI3'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                $response = curl_exec($ch);
                curl_close($ch);

                $return1 = json_encode($response);
               // print($return1);
                //return $return;
                //return new JsonResponse($return1);

    //$response['method'] = 'GET';

    return new JsonResponse( $return1 );

  }
}
