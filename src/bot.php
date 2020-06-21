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
    private $account_id;

    function __construct()
    {
        $config = require('config.php');
        $this->secret = $config['callback_secret'];
        $this->confirmation_token = $config['callback_token'];
        $this->group_id = $config['group_id'];
        $this->community_token = $config['community_token'];
        $this->api_v = $config['api_v'];
        $this->access_array = $config['access_array'];
        $this->account_id = $config['account_id'];
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
//            $message_to_send = "test";
//            $leads = Utils::getLids($vk, $user_token, $this->group_id, $timestamp_from, $timestamp_to);
            $ids_campaigns = Utils::getCampaigns($vk, $user_token, $this->account_id);
            $spent = Utils::getSpentBudget($vk,
                $user_token,
                $this->account_id,
                "campaign",
                $ids_campaigns,
                "day",
                (string)date("Y-m-d", $timestamp_from),
                (string)date("Y-m-d", $timestamp_to)
//                "0",
//                "0"
            );
//            $ids_campaigns_arr = explode(',', $ids_campaigns);
//            $spent_dict = [];
            $res = "";
            foreach ($spent as $campaign)
            {
//                if (!array_key_exists('spent', $campaign['stats'][0])) $spent_money = '0.00';
//                else $spent_money = $campaign['stats'][0]['spent'];
                $stats_money_day = $campaign['stats'];
                $spent_money = 0;
                foreach ($stats_money_day as $day)
                {
                    if (!array_key_exists("spent", $day)) continue;
                    $spent_money += $day['spent'];
                }
                if ($spent_money != 0) $res.="ID кампании: ".$campaign['id']."\nПотрачено средств: ".$spent_money."\n\n";
            }
            $spent_dict = [];
            Utils::sendMsg($vk, $this->community_token, $from, $res);
        }
        echo 'ok';
    }
}

$handler = new ServerHandler();
$data = json_decode(file_get_contents('php://input'));
$handler->parse($data);
