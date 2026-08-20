<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'guzzle' => [
            'verify' => env('APP_ENV') !== 'local',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scraper Store URLs Configuration
    |--------------------------------------------------------------------------
    */
    'scraper' => [
        'fujifruit' => array_filter(explode(',', env('SCRAPER_FUJIFRUIT_URLS', 'https://fujifruit.com.vn/danh-muc/hoa-qua-nhap-khau/'))),
        'tamfruit' => array_filter(explode(',', env('SCRAPER_TAMFRUIT_URLS', 'https://tamfruit.vn/trai-cay-nhap-khau/'))),
        'delifruit' => array_filter(explode(',', env('SCRAPER_DELIFRUIT_URLS', 'https://delifruit.vn/trai-cay-nhap-khau'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gemini AI API Configuration
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],

];
