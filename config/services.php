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

    'pegasus' => [
        'mes_usines_url' => env(
            'VERIF_MES_USINES_URL',
            'https://api.objetombrepegasus.online/api/verif/mes_usines.php'
        ),
        'mes_tickets_url' => env(
            'VERIF_MES_TICKETS_URL',
            'https://api.objetombrepegasus.online/api/verif/mes_tickets.php'
        ),
        'mes_tickets_max_pages' => (int) env('VERIF_MES_TICKETS_MAX_PAGES', 100),
        'agents_url' => env(
            'VERIF_AGENTS_URL',
            'https://api.objetombrepegasus.online/api/verif/agents.php'
        ),
        'usines_catalog_url' => env(
            'VERIF_USINES_CATALOG_URL',
            'https://api.objetombrepegasus.online/api/verif/usines.php'
        ),
    ],

];
