<?php
 

return [
    /*
    |--------------------------------------------------------------------------
    | Application Microsoft Integration
    |--------------------------------------------------------------------------
    |
    | Microsoft integration related config for connecting via oAuth.
    |
    */
    'microsoft' => [

        /**
         * The Microsoft Azure Application (client) ID
         *
         * https://portal.azure.com
         */
        'client_id' => env('MICROSOFT_CLIENT_ID'),

        /**
         * Azure application secret key
         */
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),

        /**
         * Application tenant ID
         * Use 'common' to support personal and work/school accounts
         */
        'tenant_id' => env('MICROSOFT_TENANT_ID', 'common'),

        /*
        * Set the url to trigger the OAuth process this url should call return Microsoft::connect();
        */
        'redirect_uri' => env('MICROSOFT_REDIRECT_URI', '/microsoft/callback'),

        /**
         * Callback URL
         *
         * This configuration takes precedence of the "redirect_uri" configuration value.
         */
        'redirect_url' => env('MICROSOFT_REDIRECT_URL'),

        /**
         * Login base URL
         */
        'login_url_base' => env('MICROSOFT_LOGIN_URL_BASE', 'https://login.microsoftonline.com'),

        /**
         * OAuth2 path
         */
        'oauth2_path' => env('MICROSOFT_OAUTH2_PATH', '/oauth2/v2.0'),

        /**
         * Microsoft scopes to be used, Graph API will acept up to 20 scopes
         *
         * @see https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-permissions-and-consent
         */
        'scopes' => [
            'offline_access',
            'openid',
            'User.Read',
            'Mail.ReadWrite',
            'Mail.Send',
            'MailboxSettings.ReadWrite',
            'Calendars.ReadWrite',
        ],

        /**
         * The default timezone is always set to UTC.
         */
        'prefer_timezone' => env('MS_GRAPH_PREFER_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Google Integration
    |--------------------------------------------------------------------------
    |
    | Google integration related config for connecting via oAuth.
    |
    */
    'google' => [
        /**
         * Google Project Client ID
         */
        'client_id' => env('GOOGLE_CLIENT_ID'),

        /**
         * Google Project Client Secret
         */
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),

        /**
         * Callback URI
         */
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', '/google/callback'),

        /**
         * Callback URL
         *
         * This configuration takes precedence of the "redirect_uri" configuration value.
         */
        'redirect_url' => env('GOOGLE_REDIRECT_URL'),

        /**
         * Access type
         */
        'access_type' => 'offline',

        /**
         * Scopes for OAuth
         */
        'scopes' => ['https://mail.google.com/', 'https://www.googleapis.com/auth/calendar'],
    ],
];
