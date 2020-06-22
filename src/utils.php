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

    public static function getLeads($dbconn, int $group_id, int $timestamp_from, int $timestamp_to)
    {
        $query = "SELECT COUNT(*) FROM first_msg WHERE group_id = {$group_id} " .
            "AND date BETWEEN to_timestamp({$timestamp_from}) AND to_timestamp({$timestamp_to})";
        $res = pg_query($query) or die('Query failed: ' . pg_last_error());
        return pg_fetch_result($res, 'count');
    }
}
