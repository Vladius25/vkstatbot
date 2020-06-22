<?php

return array(
    // Токен сообщества, с которой связан бот
    'community_token' => '',
    // ID группы, с которой связан бот
    'group_id' => 0,
    // ID приложения, используемое для авторизации пользователя
    'app_id' => 0,
    // Секретный ключ приложения, используемое для авторизации пользователя
    'app_secret' => '',
    // Ссылка на обработчик авторизационных запросов
    'redirect_uri' => '',
    // Файл, в котором хранится токен пользователя
    'token_file' => '',
    // Версия api vk
    'api_v' => '',
    // ID пользователей, которым доступно общение с ботом
    'access_array' => [],
    // Пользователь PostgreSQL
    'pg_user' => '',
    // Пароль от PostgreSQL
    'pg_pass' => '',
    // Список обслуживаемых сообещств
    // callback_secret - секретный ключ Callback api
    // callback_token - строка, которую должен вернуть бот для подтверждения
    'communities' => [
        //Сообщетсво с ID = 0
        0 => ['callback_secret' => '', 'callback_token' => ''],
        //Сообщетсво с ID = 1
        1 => ['callback_secret' => '', 'callback_token' => ''],
    ]
);
