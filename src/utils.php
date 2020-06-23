<?php

use VK\Client\VKApiClient;

class Utils
{
    public static function sendMsg(VKApiClient $vk, string $community_token, int $user_id, string $msg)
    {
        if (is_null($msg))
            return;
        $vk->messages()->send($community_token, [
            'user_id' => $user_id,
            'random_id' => rand(0, 9999),
            'message' => $msg
        ]);
    }

    public static function getStats(VKApiClient $vk, string $user_token, int $account_id, int $timestamp_from, int $timestamp_to)
    {
        $ids_campaigns = Utils::getCampaigns($vk, $user_token, $account_id);
        $spent = $vk->ads()->getStatistics($user_token, [
            'account_id' => $account_id,
            'ids_type' => 'campaign',
            'ids' => $ids_campaigns,
            'period' => 'day',
            'date_from' => (string)date("Y-m-d", $timestamp_from),
            'date_to' => (string)date("Y-m-d", $timestamp_to),
        ]);
        $campaigns_spent_dict = Utils::getSpentPerCampaign($spent);
        $ads_layout = $vk->ads()->getAdsLayout($user_token, ['account_id' => $account_id]);;
        $spent_dict = Utils::getSpentPerGroup($vk, $user_token, $ads_layout, $campaigns_spent_dict);
        return $spent_dict;
    }

    public static function getLeads(int $group_id, int $timestamp_from, int $timestamp_to)
    {
        $query = "SELECT COUNT(*) FROM first_msg WHERE group_id = {$group_id} " .
            "AND date BETWEEN to_timestamp({$timestamp_from}) AND to_timestamp({$timestamp_to})";
        $res = pg_query($query) or die('Query failed: ' . pg_last_error());
        return pg_fetch_result($res, 'count');
    }

    public static function getCampaigns(VKApiClient $vk, string $user_token, int $account_id)
    {
        $campaigns = $vk->ads()->getCampaigns($user_token, ['account_id' => $account_id]);
        $campaigns_str = "";
        foreach ($campaigns as $campaign) {
            $campaigns_str .= $campaign['id'] . ",";
        }
        $campaigns_str = substr($campaigns_str, 0, -1);
        return $campaigns_str;
    }

    public static function getSpentPerCampaign($spent)
    {
        $campaigns_spent_dict = [];
        foreach ($spent as $campaign) {
            $stats_money_day = $campaign['stats'];
            $spent_money = 0;
            foreach ($stats_money_day as $day) {
                if (!array_key_exists("spent", $day)) continue;
                $spent_money += $day['spent'];
            }

            if (array_key_exists($campaign['id'], $campaigns_spent_dict))
                $campaigns_spent_dict[$campaign['id']] += $spent_money;
            else
                $campaigns_spent_dict[$campaign['id']] = $spent_money;

        }
        return $campaigns_spent_dict;
    }

    public static function getSpentPerGroup(VKApiClient $vk, string $user_token, $ads_layout, $campaigns_spent_dict)
    {
        $used_campaigns = [];
        $spent_dict = [];
        foreach ($ads_layout as $layout) {
            if ($layout['ad_format'] == 1 || $layout['ad_format'] == 2 || $layout['ad_format'] == 4) {
                $url_parts = explode('/', $layout['link_url']);
                $group_title = end($url_parts);
                $group_id = $vk->groups()->getById($user_token,
                    [
                        'group_id' => $group_title,
                    ])[0]['id'];

            } else {
                preg_match('/(?<=-)\d+(?=_)/m', $layout['link_url'], $matches);
                $id_of_group = $matches[0];
                $group_id = $id_of_group;
            }

            if (!array_key_exists($group_id, $spent_dict)) {
                $spent_dict[$group_id] = 0;
            }
            if (!in_array($layout['campaign_id'], $used_campaigns)) {
                $spent_dict[$group_id] += $campaigns_spent_dict[$layout['campaign_id']];
                array_push($used_campaigns, $layout['campaign_id']);
            }
        }
        return $spent_dict;
    }
}
