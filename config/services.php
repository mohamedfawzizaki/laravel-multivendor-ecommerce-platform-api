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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN', ''), // The Mailgun domain for sending emails
        'secret' => env('MAILGUN_SECRET', 'your-key-here'), // The API key for authentication
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'), // The Mailgun API endpoint (default: api.mailgun.net)
        'scheme' => 'https', // Protocol for API requests (HTTPS ensures security)
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'), // Postmark API token for sending emails
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'), // AWS IAM access key
        'secret' => env('AWS_SECRET_ACCESS_KEY'), // AWS IAM secret key
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'), // AWS region (e.g., us-east-1)
    ],

    'resend' => [
        'key' => env('RESEND_KEY'), // API key for Resend service
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), // Slack bot token for authentication
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'), // Default Slack channel for notifications
        ],
    ],

];