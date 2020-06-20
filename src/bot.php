<?php

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';

class ServerHandler extends VKCallbackApiServerHandler
{
    private $secret;
    private $confirmation_token;
    private $group_id;
    private $api_v;
    private $community_token;

    function __construct()
    {
        $config = require('config.php');
        $this->secret = $config['callback_secret'];
        $this->confirmation_token = $config['callback_token'];
        $this->group_id = $config['group_id'];
        $this->community_token = $config['community_token'];
        $this->api_v = $config['api_v'];
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
        if ($from == 135641618) {
            $vk = new VKApiClient($this->api_v);
            $auth = new Authorization();
            $user_token = $auth->getToken();
            if(is_null($user_token)) {
                Utils::sendMsg($vk, static::ACCESS_TOKEN, $from, "Необходимо авторизоваться");
                Utils::sendMsg($vk, static::ACCESS_TOKEN, $from, $auth->makeTokenRequest());
                exit(0);
            }
            $message_text = $object['message']->text;
            $splitted_dates = explode('-', $message_text);
            $timestamp_from = strtotime($splitted_dates[0]);
            $timestamp_to = strtotime($splitted_dates[1]);
            $result = Utils::getLids($vk, $user_token, $this->group_id, $timestamp_from, $timestamp_to);
            Utils::sendMsg($vk, $this->community_token, $from, 'test');
        }
        echo 'ok';
    }
}

$handler = new ServerHandler();
$data = json_decode(file_get_contents('php://input'));
$handler->parse($data);
