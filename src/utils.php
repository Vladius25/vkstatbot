<?php

use VK\Client\VKApiClient;

class Utils
{
    public static function sendMsg(VKApiClient $vk, String $community_token, int $user_id, String $msg)
    {
        if(is_null($msg))
            return;
        $vk->messages()->send($community_token, [
            'user_id' => $user_id,
            'random_id' => rand(0, 9999),
            'message' => $msg
        ]);
    }

    public static function getLids(VKApiClient $vk, String $user_token, int $group_id, int $timestamp_from, int $timestamp_to)
    {
        return $vk->stats()->get($user_token, [
            'group_id' => $group_id,
            'timestamp_from' => $timestamp_from,
            'timestamp_to' => $timestamp_to
        ]);
    }

    public static function getCampaigns(VKApiClient $vk, string $user_token, int $account_id)
    {
        $campaigns = $vk->ads()->getCampaigns($user_token, ['account_id' => $account_id]);
        $campaigns_str = "";
        foreach ($campaigns as $campaign)
        {
            $campaigns_str.=$campaign['id'].",";
        }
        $campaigns_str = substr($campaigns_str,0,-1);
        return $campaigns_str;
    }

    public static function getSpentBudget(VKApiClient $vk,
                                          string $user_token,
                                          int $account_id,
                                          string $ids_type,
                                          string $ids,
                                          string $period,
                                          string $date_from,
                                          string $date_to)
    {
        return $vk->ads()->getStatistics($user_token, [
            'account_id' => $account_id,
            'ids_type' => $ids_type,
            'ids' => $ids,
            'period' => $period,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ]);
    }
}
