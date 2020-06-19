<?php

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

require __DIR__ . '/vendor/autoload.php';


//
//
//
//$vk = new VKApiClient('5.110');
//$response = $vk->messages()->send($access_token, [
//    'user_id'  => 135641618,
//    'random_id' => rand(0, 9999),
//    'message'    => 'hello'
//]);
//
//$response = $vk->messages()->getHistory($access_token, [
//    'offset' => 0,
//    'count' => 1,
//    'user_id'  => 135641618
//]);

class ServerHandler extends VKCallbackApiServerHandler
{
    const SECRET = 'Oquuo5aiChei6ah';
    const GROUP_ID = 152396040;
    const CONFIRMATION_TOKEN = '23981ea6';
    const ACCESS_TOKEN = '5f9071c17793443349971f3dc00ac71fa16d25e1c81cd39e8cb6447075499627dc4c9108a0713785dc9bb';

    function confirmation(int $group_id, ?string $secret)
    {
        if ($secret === static::SECRET && $group_id === static::GROUP_ID) {
            echo static::CONFIRMATION_TOKEN;
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        $vk = new VKApiClient('5.110');
        $from = $object['message']->from_id;
        $text_data = $object['message']->text;
        $date = strtotime($text_data);
        if ($from == 135641618)
        {
            $response = $vk->messages()->send(static::ACCESS_TOKEN, [
                'user_id' => 135641618,
                'random_id' => rand(0, 9999),
                'message' => $date
            ]);
        }
        echo 'ok';
    }
}

$handler = new ServerHandler();
$data = json_decode(file_get_contents('php://input'));
$handler->parse($data);