<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            // Defines the mail transport method as 'smtp' (Simple Mail Transfer Protocol)
            'transport' => 'smtp',
            // Specifies the mail scheme, which defines how emails are transmitted (e.g., 'smtp', 'smtps')
            'scheme' => env('MAIL_SCHEME', 'smtp'),
            // The full SMTP connection URL (if available), typically used instead of separate host/port settings
            // 'url' => env('MAIL_URL', 'smtp://username:password@mailgun.org:587'),
            // The SMTP server hostname (Mailgun, SendGrid, or any other SMTP provider)
            // Defaults to '127.0.0.1' if not set in the .env file
            'host' => env('MAIL_HOST', 'gmail.com'), // mailgun.org
            // The port used for SMTP communication (e.g., 587 for TLS, 465 for SSL, 25 for unencrypted SMTP)
            // Defaults to 2525, which is often used by third-party mail services for secure email delivery
            'port' => env('MAIL_PORT', 2525),
            // The SMTP username for authentication (usually an email address or API-based user)
            'username' => env('MAIL_USERNAME'),
            // The SMTP password or API key for authentication (must be kept secure)
            'password' => env('MAIL_PASSWORD'),
            // Timeout value for the SMTP connection (null means it will use default settings)
            'timeout' => null,
            // The local domain used in the EHLO (Extended Hello) command when connecting to the SMTP server
            // Defaults to the host extracted from APP_URL if MAIL_EHLO_DOMAIN is not explicitly set
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],


        'mailgun' => [
            'transport' => 'mailgun', // This tells Laravel to use Mailgun's API instead of SMTP for sending emails.
            // 'client' => [
            //     'timeout' => 5, // Laravel will wait 5 seconds before giving up on a request. Helps prevent delays.
            //     'connect_timeout' => 3, // Maximum time (in seconds) to wait while trying to establish a connection.
            //     'debug' => false, // Set to true to log request/response details for debugging.
            //     'verify' => true, // SSL certificate verification (set to false for development but true in production).
            //     'http_errors' => true, // If true, Laravel will throw exceptions for HTTP errors (4xx, 5xx).
            //     'headers' => [ // Custom headers to send with the request.
            //         'User-Agent' => 'Laravel-Mailgun-Client/1.0',
            //         'X-Custom-Header' => 'CustomValue',
            //     ],
            //     'proxy' => 'http://proxy.example.com:8080', // Allows routing requests through an HTTP proxy.
            //     'allow_redirects' => true, // Enable/disable automatic redirection for API requests.
            // ],
        ],


        'ses' => [
            'transport' => 'ses',
            // 'client' => [
            //     'timeout' => 10, // Maximum time in seconds to wait for a response
            //     'connect_timeout' => 5, // Time in seconds to wait for a connection to be established
            //     'debug' => false, // Set to true for debugging HTTP requests (useful for testing)
            //     'verify' => true, // SSL certificate verification (set to false to disable)
            //     'http_errors' => true, // Whether to throw exceptions on HTTP errors (set to false to handle manually)
            //     'headers' => [ // Custom HTTP headers for API requests
            //         'X-Custom-Header' => 'CustomValue',
            //     ],
            //     'proxy' => 'http://proxy.example.com:8080', // Use an HTTP proxy if required
            //     'allow_redirects' => true, // Allow HTTP redirections
            // ],
        ],


        'postmark' => [
            'transport' => 'postmark', // Specifies Postmark as the email service provider.
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'), // Optional: Defines the message stream for sending different types of emails.
            // 'client' => [
            //     'timeout' => 5, // Maximum time (in seconds) to wait for a response before failing.
            //     'connect_timeout' => 3, // Maximum time to wait while establishing a connection to Postmark.
            //     'debug' => false, // If true, logs raw HTTP requests and responses for debugging.
            //     'verify' => true, // Enables SSL certificate verification (set to false for development only).
            //     'http_errors' => true, // If true, Laravel throws an exception on HTTP errors (4xx, 5xx).
            //     'headers' => [ // Custom HTTP headers sent with API requests.
            //         'User-Agent' => 'Laravel-Postmark-Client/1.0',
            //         'X-Custom-Header' => 'CustomValue',
            //     ],
            //     'proxy' => 'http://proxy.example.com:8080', // Routes requests through a proxy.
            //     'allow_redirects' => true, // Determines if API requests should follow HTTP redirects.
            // ],
        ],


        'resend' => [
            'transport' => 'resend', // Uses Resend's API for email delivery.
            // 'client' => [
            //     'timeout' => 5, // Sets the maximum wait time (in seconds) before a request fails.
            //     'connect_timeout' => 3, // Limits the connection time to Resend’s API.
            //     'debug' => false, // If true, logs raw request/response data for debugging.
            //     'verify' => true, // Enables SSL certificate verification for security.
            //     'http_errors' => true, // If true, Laravel will throw exceptions on HTTP errors (4xx, 5xx).
            //     'headers' => [ // Custom HTTP headers sent with the API requests.
            //         'User-Agent' => 'Laravel-Resend-Client/1.0',
            //         'X-Custom-Header' => 'CustomValue',
            //     ],
            //     'proxy' => 'http://proxy.example.com:8080', // Routes requests through an HTTP proxy.
            //     'allow_redirects' => true, // Determines if API requests should follow HTTP redirects.
            // ],
        ],


        'sendmail' => [
            'transport' => 'sendmail', // Uses the Sendmail binary for email delivery.
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'), // Path to the Sendmail binary.
            // 'client' => [
            //     'timeout' => 10, // Sets the maximum time Laravel will wait for the email to be sent.
            //     'debug' => false, // Enables logging of email sending details.
            //     'headers' => [
            //         'X-Mailer' => 'Laravel-Sendmail', // Custom header to identify emails sent via Laravel.
            //     ],
            // ],
        ],


        'log' => [
            'transport' => 'log', // Uses logging instead of actually sending emails
            'channel' => env('MAIL_LOG_CHANNEL', 'mail'), // Defines the logging channel for emails
            // 'client' => [
            //     'debug' => true, // Enables debugging mode for email logging
            //     'log_level' => 'info', // Sets log level (e.g., debug, info, warning, error)
            //     'format' => 'json', // Formats email logs as JSON instead of plain text
            //     'include_headers' => true, // Logs email headers (To, From, Subject, etc.)
            //     'include_body' => true, // Logs the email body for debugging
            // ],
        ],


        'array' => [
            // The array transport in Laravel is used for testing purposes. 
            // Instead of sending emails, Laravel stores them in an array, allowing you to inspect them programmatically.
            'transport' => 'array', // Stores emails in an array instead of sending them
        ],

        'failover' => [
            'transport' => 'failover', // Enables failover mechanism
            'mailers' => [
                'smtp',
                'log',
            ],
            //For dynamic failover, you can update your .env file:
            // 'mailers' => explode(',', env('MAIL_FAILOVER_MAILERS', 'smtp,log')),
        ],

        'roundrobin' => [
            'transport' => 'roundrobin', // Enables round-robin email distribution
            'mailers' => [
                'ses',       // First mailer (Amazon SES)
                'postmark',  // Second mailer (Postmark)
            ],
            // 'mailers' => explode(',', env('MAIL_ROUNDROBIN_MAILERS', 'ses,postmark')),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'YourApp'),
    ],

    'reply_to' => [
        'address' => env('MAIL_REPLY_TO_ADDRESS', 'support@example.com'),
        'name' => env('MAIL_REPLY_TO_NAME', 'Support Team'),
    ],

];