<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

class ServerHandler extends VKCallbackApiServerHandler
{
    private $secret;
    private $confirmation_token;
    private $group_id;
    private $api_v;
    private $community_token;
    private $access_array;

    function __construct()
    {
        $config = require('config.php');
        $this->secret = $config['callback_secret'];
        $this->confirmation_token = $config['callback_token'];
        $this->group_id = $config['group_id'];
        $this->community_token = $config['community_token'];
        $this->api_v = $config['api_v'];
        $this->access_array = $config['access_array'];
    }

    function confirmation(int $group_id, ?string $secret)
    {
        if ($secret === $this->secret && $group_id === $this->group_id) {
            echo $this->confirmation_token;
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        $from = $object['message']->from_id;
        if (in_array($from, $this->access_array)) {
            $vk = new VKApiClient($this->api_v);
            $auth = new Authorization();
            $user_token = $auth->getToken();
            if(is_null($user_token)) {
                Utils::sendMsg($vk, $this->community_token, $from, "Необходимо авторизоваться");
                Utils::sendMsg($vk, $this->community_token, $from, $auth->makeTokenRequest());
                die('ok');
            }
            $message_text = $object['message']->text;
            $splitted_dates = explode('-', $message_text);
            if (count($splitted_dates) != 2) {
                die('ok');
            }
            $timestamp_from = strtotime($splitted_dates[0]);
            $timestamp_to = strtotime($splitted_dates[1]);
            if ($timestamp_from == False || $timestamp_to == False){
                die('ok');
            }
            $message_to_send = "test";
            $result = Utils::getLids($vk, $user_token, $this->group_id, $timestamp_from, $timestamp_to);
            Utils::sendMsg($vk, $this->community_token, $from, $message_to_send);
        }
        echo 'ok';
    }
}

$handler = new ServerHandler();
$data = json_decode(file_get_contents('php://input'));
$handler->parse($data);
