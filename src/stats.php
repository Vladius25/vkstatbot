<?php
require __DIR__ . '/vendor/autoload.php';

use VK\OAuth\Scopes\VKOAuthUserScope;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\VKOAuthResponseType;

$oauth = new VKOAuth();
$client_id = 7515449;
$redirect_uri = 'http://localhost/main.php';
$display = VKOAuthDisplay::PAGE;
$scope = array(VKOAuthUserScope::STATS);
$state = 'fgfgUfff';

if(!isset($_GET['code'])) {
    $browser_url = $oauth->getAuthorizeUrl(VKOAuthResponseType::CODE, $client_id, $redirect_uri, $display, $scope, $state);
    echo $browser_url;
} else {
    $code = $_GET['code'];
    $client_secret = 'QiaWAGimh6wnaGX10nV1';
    $response = $oauth->getAccessToken($client_id, $client_secret, $redirect_uri, $code);
    $access_token = $response['access_token'];

    echo $access_token;
    $vk = new VK\Client\VKApiClient();
    $stats = $vk->stats()->get($access_token, [
        'group_id' => 152396040,
        'timestamp_from' => 1592438400,
        'timestamp_to' => 1592524799,
    ]);
    var_dump($stats);
    echo $stats;
}
