<?php

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';

class ServerHandler extends VKCallbackApiServerHandler
{
    const SECRET = 'Oquuo5aiChei6ah';
    const GROUP_ID = 152396040;
    const CONFIRMATION_TOKEN = 'd2a60eda';
    const ACCESS_TOKEN = '5f9071c17793443349971f3dc00ac71fa16d25e1c81cd39e8cb6447075499627dc4c9108a0713785dc9bb';

    function confirmation(int $group_id, ?string $secret)
    {
        if ($secret === static::SECRET && $group_id === static::GROUP_ID) {
            echo static::CONFIRMATION_TOKEN;
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        $from = $object['message']->from_id;
        if($from == 135641618) {
            $vk = new VKApiClient('5.110');
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
            $result = Utils::getLids($vk, $user_token, static::GROUP_ID, $timestamp_from, $timestamp_to);
            Utils::sendMsg($vk, static::ACCESS_TOKEN, $from, 'test');
        }
        echo 'ok';
    }
}

$handler = new ServerHandler();
$data = json_decode(file_get_contents('php://input'));
$handler->parse($data);
