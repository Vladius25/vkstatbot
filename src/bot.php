<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

class ServerHandler extends VKCallbackApiServerHandler
{
    private $community_token;
    private $group_id;
    private $api_v;
    private $access_array;
    private $communities;
    private $pg_pass;

    function __construct()
    {
        $config = require('config.php');
        $this->community_token = $config['community_token'];
        $this->group_id = $config['group_id'];
        $this->api_v = $config['api_v'];
        $this->access_array = $config['access_array'];
        $this->communities = $config['communities'];
        $this->pg_pass = $config['pg_pass'];
    }

    function confirmation(int $group_id, ?string $secret)
    {
        if(!array_key_exists($group_id, $this->communities))
            exit("Group is not in allowed list");
        $group = $this->communities[$group_id];
        if ($secret === $group['callback_secret']) {
            echo $group['callback_token'];
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        $from = $object['message']->from_id;
        $dbconn = pg_connect("host=localhost dbname=vkstatbot user=postgres password={$this->pg_pass}}")
            or die('Could not connect: ' . pg_last_error());
        if ($group_id === $this->group_id and in_array($from, $this->access_array)) {
            $vk = new VKApiClient($this->api_v);
            $auth = new Authorization();
            $user_token = $auth->getToken();
            if (is_null($user_token)) {
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
            if ($timestamp_from == False || $timestamp_to == False) {
                die('ok');
            }
            foreach ($this->communities as $key => $value) {
                $leads_amount = Utils::getLeads($dbconn, $key, $timestamp_from, $timestamp_to);
                Utils::sendMsg($vk, $this->community_token, $from, "Лидов в группе ".$key." за указанный период: ".$leads_amount);
            }
        } else {
            date_default_timezone_set('Europe/Moscow');
            $date = date('Y-m-d H:i:s');
            $query = "INSERT INTO first_msg (group_id, user_id, date) VALUES ({$group_id}, {$from}, '{$date}') ON CONFLICT DO NOTHING;";
            pg_query($query) or die('Query failed: ' . pg_last_error());
        }
        echo 'ok';
    }
}

$handler = new ServerHandler();
$data = json_decode(file_get_contents('php://input'));
$handler->parse($data);
