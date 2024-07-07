<?php
 

return [
    'name' => 'Core',

    /*
    |--------------------------------------------------------------------------
    | Application logo config
    |--------------------------------------------------------------------------
    |
    */
    'logo' => [
        'light' => env('LIGHT_LOGO_URL'),
        'dark' => env('DARK_LOGO_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Unique Identification Key
    |--------------------------------------------------------------------------
    */
    'key' => env('IDENTIFICATION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Application Date Format
    |--------------------------------------------------------------------------
    |
    | Application date format, the value is used when performing formats for to
    | local date via the available formatters.
    |
    */

    'date_format' => 'F j, Y',

    /*
    |--------------------------------------------------------------------------
    | Application Time Format
    |--------------------------------------------------------------------------
    |
    | Application time format, the value is used when performing formats for to
    | local datetime via the available formatters.
    |
    */

    'time_format' => 'H:i:s',

    /*
    |--------------------------------------------------------------------------
    | Application Currency
    |--------------------------------------------------------------------------
    |
    | The application currency, is used on a specific features e.q. form groups
    |
    */
    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | reCaptcha configuration
    |--------------------------------------------------------------------------
    |
    | reCaptcha configuration to provide additional security.
    |
    */
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', null),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', null),
        'ignored_ips' => env('RECAPTCHA_IGNORED_IPS', []),
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft deletes config
    |--------------------------------------------------------------------------
    |
    */
    'soft_deletes' => [
        'prune_after' => env('PRUNE_TRASHED_RECORDS_AFTER', 30), // in days
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailable templates configuration
    |--------------------------------------------------------------------------
    |
    | layout => The mailable templates default layout path
    |
    */

    'mailables' => [
        'layout' => env('MAILABLE_TEMPLATE_LAYOUT', storage_path('mail-layouts/mailable-template.html')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specificy the default directory where the media files
    | will be uploaded, keep in mind that the application will create
    | folder tree in this directory according to custom logic e.q.
    | /media/contacts/:id/image.jpg
    |
    */
    'media' => [
        'directory' => env('MEDIA_DIRECTORY', 'media'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application favourite colors
    |--------------------------------------------------------------------------
    |
    */
    'colors' => explode(',', env(
        'COMMON_COLORS',
        '#374151,#DC2626,#F59E0B,#10B981,#2563EB,#4F46E5,#7C3AED,#EC4899,#F3F4F6'
    )),

    /*
    |--------------------------------------------------------------------------
    | Application actions config
    |--------------------------------------------------------------------------
    |
    */
    'actions' => [
        'disable_notifications_more_than' => env('DISABLE_ACTIONS_NOTIFICATIONS_WHEN_MORE_THAN', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application oAuth config
    |--------------------------------------------------------------------------
    |
    */
    'oauth' => [
        'state' => [
            /**
             * State storage driver
             */
            'storage' => 'session',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The application import configuration
    |--------------------------------------------------------------------------
    |
    | Define configuration like max import rows support.
    |
    */
    'import' => [
        'revertable_hours' => env('IMPORT_REVERTABLE_HOURS', 24),
        'max_rows' => env('MAX_IMPORT_ROWS', 4000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources configuration
    |--------------------------------------------------------------------------
    |
    | Define configuration like permissions common provider.
    |
    */
    'resources' => [
        /**
         * Register the resources common permissions provider
         */
        'permissions' => [
            'common' => \Modules\Core\App\Common\PermissionsProvider::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Allowed Date Formats
    |--------------------------------------------------------------------------
    |
    | The application date format that the users are able to use.
    |
    */
    'date_formats' => [
        'd-m-Y',
        'd/m/Y',
        'm-d-Y',
        'm.d.Y',
        'm/d/Y',
        'Y-m-d',
        'd.m.Y',
        'F j, Y',
        'j F, Y',
        'D, F j, Y',
        'l, F j, Y',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Allowed Time Formats
    |--------------------------------------------------------------------------
    |
    | The application time format that the users are able to use.
    |
    */
    'time_formats' => [
        'H:i',
        'h:i A',
        'h:i a',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application favicon
    |--------------------------------------------------------------------------
    | Here you may enable favicon to be included, but first you must generate
    | the favicons via https://realfavicongenerator.net/ and upload the .zip file
    | contents in /public/favicons.
    |
    | More info: https://www.crm.com/docs/favicon
    |
    */
    'favicon_enabled' => env('ENABLE_FAVICON', false),

    'defaults' => [
        'reminder_minutes' => env('PREFERRED_DEFAULT_REMINDER_MINUTES', 30),
    ],

    /**
     * Common commands.
     */
    'commands' => [
        'optimize' => 'core:optimize',
        'clear-cache' => 'core:clear-cache',
    ],
];
