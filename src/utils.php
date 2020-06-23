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
        sleep(1);
        $spent = $vk->ads()->getStatistics($user_token, [
            'account_id' => $account_id,
            'ids_type' => 'campaign',
            'ids' => $ids_campaigns,
            'period' => 'day',
            'date_from' => (string)date("Y-m-d", $timestamp_from),
            'date_to' => (string)date("Y-m-d", $timestamp_to),
        ]);
        sleep(1);
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
            $campaigns_str .= $campaign['id'] . ',';
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

    public static function getScreenName(string $uri) {
        $url_parts = explode('/', $uri);
        return end($url_parts);
    }

    public static function getGroupsIdsByLayouts(VKApiClient $vk, $ads_layout, string $user_token)
    {
        $named_groups = [];
        $screen_names = [];
        foreach ($ads_layout as $layout) {
            if (in_array($layout['ad_format'], [1, 2, 4])) {
                $screen_name = self::getScreenName($layout['link_url']);
                array_push($screen_names, $screen_name);
            }
        }
        $screen_names = implode(',', array_unique($screen_names));
        $groups = $vk->groups()->getById($user_token, ['group_ids' => $screen_names]);
        foreach ($groups as $group)
            $named_groups [$group['screen_name']] = $group['id'];

        return $named_groups;
    }

    public static function getSpentPerGroup(VKApiClient $vk, string $user_token, $ads_layout, $campaigns_spent_dict)
    {
        $used_campaigns = [];
        $spent_dict = [];
        $named_groups = self::getGroupsIdsByLayouts($vk, $ads_layout, $user_token);
        foreach ($ads_layout as $layout) {
            if (in_array($layout['ad_format'], [1, 2, 4])) {
                $screen_name = self::getScreenName($layout['link_url']);
                $group_id = $named_groups[$screen_name];
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
