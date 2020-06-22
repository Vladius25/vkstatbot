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
            );
            $campaigns_spent_dict = [];
            foreach ($spent as $campaign) {
                $stats_money_day = $campaign['stats'];
                $spent_money = 0;
                foreach ($stats_money_day as $day) {
                    if (!array_key_exists("spent", $day)) continue;
                    $spent_money += $day['spent'];
                }

                if (array_key_exists($campaign['id'], $campaigns_spent_dict)) $campaigns_spent_dict[$campaign['id']] += $spent_money;
                else $campaigns_spent_dict[$campaign['id']] = $spent_money;

            }
            $ads_layout = Utils::getAds($vk, $user_token, $this->account_id);
            $spent_dict = [];
            $used_campaigns = [];
            foreach ($ads_layout as $layout) {
                if ($layout['ad_format'] == 1 || $layout['ad_format'] == 2 || $layout['ad_format'] == 4)
                {
                    $link_of_group = $layout['link_url'];
                }
                else {
                    preg_match('/(?<=-)\d+(?=_)/m', $layout['link_url'], $matches);
                    $id_of_group = $matches[0];
                    $link_of_group = "http://vk.com/club".$id_of_group;
                }

                if (!array_key_exists($link_of_group, $spent_dict)) {
                    $spent_dict[$link_of_group] = 0;
                }
                if (!in_array($layout['campaign_id'], $used_campaigns)) {
                    $spent_dict[$link_of_group] += $campaigns_spent_dict[$layout['campaign_id']];
                    array_push($used_campaigns, $layout['campaign_id']);
                }
            }
            $res = "";
            foreach ($spent_dict as $group_link => $spent) {
                if ($spent != 0)
                {
                    $res .= "ID группы: " . $group_link . "\nПотрачено средств: " . $spent . "\n\n";
                }
            }
            Utils::sendMsg($vk, $this->community_token, $from, $res);
        }
        echo 'ok';
    }
}

$handler = new ServerHandler();
$data = json_decode(file_get_contents('php://input'));
$handler->parse($data);
