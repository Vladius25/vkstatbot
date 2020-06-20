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
}
