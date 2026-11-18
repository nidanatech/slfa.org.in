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

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/channels/facebook/callback',
        'webhook_verify_token' => env('FACEBOOK_WEBHOOK_VERIFY_TOKEN'),
    ],

    'threads' => [
        'client_id' => env('THREADS_CLIENT_ID'),
        'client_secret' => env('THREADS_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/channels/threads/callback',
        'webhook_verify_token' => env('THREADS_WEBHOOK_VERIFY_TOKEN'),
    ],

    'instagram' => [
        'client_id' => env('INSTAGRAM_CLIENT_ID', env('FACEBOOK_CLIENT_ID')),
        'client_secret' => env('INSTAGRAM_CLIENT_SECRET', env('FACEBOOK_CLIENT_SECRET')),
        'redirect' => 'https://slfa.org.in/user/ig/callback',
        'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', env('FACEBOOK_WEBHOOK_VERIFY_TOKEN')),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'analytics_id' => env('GA_MEASUREMENT_ID'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'), // Legacy/shared
    ],

    'gmail' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GMAIL_REDIRECT_URI', env('GOOGLE_REDIRECT_URI')),
    ],

    'google_business' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_BUSINESS_REDIRECT_URI'),
    ],

    'ghl' => [
        'client_id' => env('GHL_CLIENT_ID'),
        'client_secret' => env('GHL_CLIENT_SECRET'),
        'redirect_uri' => env('APP_URL') . '/integrations/ghl/callback',
    ],

    'whatsapp_baileys' => [
        'url' => env('WHATSAPP_BAILEYS_URL', 'http://localhost:3001'),
    ],

];
