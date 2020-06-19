<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/utils.php';

use VK\Client\VKApiClient;
use VK\Exceptions\VKApiException;
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

class Authorization {
    const TOKEN_FILE = 'token';
    const DISPLAY = VKOAuthDisplay::PAGE;
    const STATE = "sadgdshsfdh";
    const SCOPE = array(VKOAuthUserScope::STATS, VKOAuthUserScope::ADS, VKOAuthUserScope::OFFLINE);
    const ACCESS_TOKEN = '5f9071c17793443349971f3dc00ac71fa16d25e1c81cd39e8cb6447075499627dc4c9108a0713785dc9bb';
    const GROUP_ID = 152396040;
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    function __construct() {
        $this->client_id = 7515449;
        $this->client_secret = 'QiaWAGimh6wnaGX10nV1';
        $this->redirect_uri = 'http://localhost:8080/auth.php';
    }

    function makeTokenRequest() {
        $oauth = new VKOAuth();
        return $oauth->getAuthorizeUrl(
            VKOAuthResponseType::CODE,
            $this->client_id,
            $this->redirect_uri,
            static::DISPLAY,
            static::SCOPE,
            static::STATE
        );
    }

    function makeToken() {
        if(isset($_GET['code'])) {
            $code = $_GET['code'];
            $oauth = new VKOAuth();
            $vk = new VKApiClient('5.110');
            $response = $oauth->getAccessToken($this->client_id, $this->client_secret, $this->redirect_uri, $code);
            file_put_contents(static::TOKEN_FILE, $response['access_token']);
            Utils::sendMsg($vk, static::ACCESS_TOKEN, $response['user_id'], "Вы успешно авторизованы");
            echo '<script>window.close();</script>';
        }
        echo 'Ошибка: не задан code';
    }

    function getToken() {
        $token = file_get_contents(static::TOKEN_FILE);
        if($this->isTokenValid($token)) {
            return $token;
        }
        return null;
    }

    function isTokenValid(String $token) {
        $vk = new VKApiClient("5.110");
        try {
            Utils::getLids($vk, $token, static::GROUP_ID, 1, 1);
            return True;
        } catch (VKApiException $e) {
            return False;
        }
    }
}

$auth = new Authorization();
$auth->makeToken();
