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
    private $pg_user;
    private $pg_pass;

    function __construct()
    {
        $config = require('config.php');
        $this->community_token = $config['community_token'];
        $this->group_id = $config['group_id'];
        $this->api_v = $config['api_v'];
        $this->access_array = $config['access_array'];
        $this->communities = $config['communities'];
        $this->pg_user = $config['pg_user'];
        $this->pg_pass = $config['pg_pass'];
    }

    function confirmation(int $group_id, ?string $secret)
    {
        if (!array_key_exists($group_id, $this->communities))
            exit("Group is not in allowed list");
        $group = $this->communities[$group_id];
        if ($secret === $group['callback_secret']) {
            echo $group['callback_token'];
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        $from = $object['message']->from_id;
        pg_connect("host=db dbname=vkstatbot user={$this->pg_user} password={$this->pg_pass}")
        or die('Could not connect: ' . pg_last_error());
        if ($group_id === $this->group_id and in_array($from, $this->access_array)) {
            $vk = new VKApiClient($this->api_v);
            $auth = new Authorization();
            $user_token = $auth->getToken();
            if (is_null($user_token)) {
                Utils::sendMsg($vk, $this->community_token, $from, "Необходимо авторизоваться");
                Utils::sendMsg($vk, $this->community_token, $from, $auth->makeTokenRequest());
                exit('ok');
            }
            $message_text = $object['message']->text;
            $chunks = explode(' ', $message_text);
            $account_id = $chunks[1];
            $splitted_dates = explode('-', $chunks[0]);
            if (count($splitted_dates) != 2 || is_null($account_id)) {
                exit('ok');
            }

            $timestamp_from = strtotime("{$splitted_dates[0]} 00:00:00");
            $timestamp_to = strtotime("{$splitted_dates[1]} 23:59:59");
            if ($timestamp_from == False || $timestamp_to == False) {
                exit('ok');
            }
            $message_to_send = "";
            $spent_dict = Utils::getStats($vk, $user_token, $account_id, $timestamp_from, $timestamp_to);
            foreach ($spent_dict as $id => $spent) {
                $leads_amount = Utils::getLeads($id, $timestamp_from, $timestamp_to);
                $group_link = "vk.com/club" . $id;
                if ($spent_dict[$id] != 0)
                    $message_to_send .= "Сообщество: " . $group_link .
                        "\nПотрачено: " . $spent_dict[$id] .
                        "\nКоличество лидов: " . $leads_amount .
                        "\nЦена за лид: " . ($spent / $leads_amount) . "\n\n";

            }
            if ($message_to_send != "")
                Utils::sendMsg($vk, $this->community_token, $from, $message_to_send);
            else
                die('error');
        } else {
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
