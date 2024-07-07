<?php
 

return [
    'name' => 'MailClient',

    'reply_prefix' => env('MAIL_MESSAGE_REPLY_PREFIX', 'RE: '),

    'forward_prefix' => env('MAIL_MESSAGE_FORWARD_PREFIX', 'FW: '),

    /*
    |--------------------------------------------------------------------------
    | Mail client configuration
    |--------------------------------------------------------------------------
    |
    */
    'sync' => [
        /*
        |--------------------------------------------------------------------------
        | Sync mail client interval definition in cron style
        |--------------------------------------------------------------------------
        |
        | By default the mail client synchronizer, sync emails every 3 minutes, the interval can be defined below.
        */
        'interval' => env('MAIL_CLIENT_SYNC_INTERVAL', '*/3 * * * *'),
    ],
];
