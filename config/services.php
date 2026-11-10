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

    'clarity' => ['id' => env('CLARITY_PROJECT_ID')],
    'meta'    => ['pixel_id' => env('META_PIXEL_ID')],
    'google'  => ['gtag_id' => env('GOOGLE_GTAG_ID')],

    'suri' => [
        'base_url'      => env('SURI_API_URL', ''),
        'token'         => env('SURI_API_TOKEN', ''),
        'channel_id'    => env('SURI_CHANNEL_ID', ''),
        'webhook_token' => env('SURI_WEBHOOK_TOKEN', ''),
        'webhook_async' => env('SURI_WEBHOOK_ASYNC', true),
        'test_user_id'  => env('SURI_TEST_USER_ID', ''),
    ],

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

];
