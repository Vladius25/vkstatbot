<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utils.php';

use VK\Client\VKApiClient;
use VK\Exceptions\VKApiException;
use VK\OAuth\Scopes\VKOAuthUserScope;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\VKOAuthResponseType;

class Authorization
{
    const DISPLAY = VKOAuthDisplay::PAGE;
    const SCOPE = array(VKOAuthUserScope::STATS, VKOAuthUserScope::ADS, VKOAuthUserScope::OFFLINE);
    const STATE = "dngjksdhg";
    private $token_file;
    private $group_token;
    private $group_id;
    private $app_id;
    private $app_secret;
    private $redirect_uri;
    private $api_v;

    function __construct()
    {
        $config = require('config.php');
        $this->app_id = $config['app_id'];
        $this->app_secret = $config['app_secret'];
        $this->redirect_uri = $config['redirect_uri'];
        $this->token_file = $config['token_file'];
        $this->group_token = $config['community_token'];
        $this->group_id = $config['group_id'];
        $this->api_v = $config['api_v'];
    }

    function makeTokenRequest()
    {
        $oauth = new VKOAuth();
        return $oauth->getAuthorizeUrl(
            VKOAuthResponseType::CODE,
            $this->app_id,
            $this->redirect_uri,
            static::DISPLAY,
            static::SCOPE,
            static::STATE
        );
    }

    function makeToken()
    {
        $code = $_GET['code'];
        $oauth = new VKOAuth();
        $vk = new VKApiClient($this->api_v);
        $response = $oauth->getAccessToken($this->app_id, $this->app_secret, $this->redirect_uri, $code);
        file_put_contents($this->token_file, $response['access_token']);
        Utils::sendMsg($vk, $this->group_token, $response['user_id'], "Вы успешно авторизованы");
        echo /** @lang js */ '<script>window.close();</script>';
        exit(0);
    }

    function getToken()
    {
        $token = file_get_contents($this->token_file);
        if ($this->isTokenValid($token)) {
            return $token;
        }
        return null;
    }

    function isTokenValid(string $token)
    {
        $vk = new VKApiClient($this->api_v);
        try {
            Utils::getLids($vk, $token, $this->group_id, 1, 1);
            return True;
        } catch (VKApiException $e) {
            return False;
        }
    }
}

if (isset($_GET['code'])) {
    $auth = new Authorization();
    $auth->makeToken();
}
