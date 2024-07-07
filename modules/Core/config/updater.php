<?php
 

return [
    /*
    |--------------------------------------------------------------------------
    | Version installed
    |--------------------------------------------------------------------------
    |
    | Application current installed version.
    */

    'version_installed' => \Modules\Core\App\Application::VERSION,

    /*
    |--------------------------------------------------------------------------
    | General configuration for the updater
    |--------------------------------------------------------------------------
    */

    'archive_url' => env('UPDATER_ARCHIVE_URL', 'https://archive.crm.com'),
    'patches_archive_url' => env('PATCHES_ARCHIVE_URL', 'https://archive.crm.com/patches'),
    'purchase_key' => env('PURCHASE_KEY', ''),
    'download_path' => env('UPDATER_DOWNLOAD_PATH', storage_path('updater')),

    /*
    |--------------------------------------------------------------------------
    | Exclude files from update
    |--------------------------------------------------------------------------
    |
    | Specify files which should not be updated and will be skipped during the
    | update process.
    |
    */
    'exclude_files' => [
        'public/.htaccess',
        'public/web.config',
        'public/robots.txt',
        'public/favicon.ico',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude folders from update
    |--------------------------------------------------------------------------
    |
    | Specify folders which should not be updated and will be skipped during the
    | update process.
    |
    */
    'exclude_folders' => [
        '.git',
        '.idea',
        '__MACOSX',
        'node_modules',
        'bootstrap/cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions checker configuration
    |--------------------------------------------------------------------------
    |
    | Specify folders which should be excluded from the permissions checker.
    |
    */
    'permissions' => [
        'exclude_folders' => [
            'node_modules',
            'tests/coverage',
            '_crm',
            'storage/app',
            'storage/framework',
            'storage/debugbar',
            'storage/logs',
            'public/storage',
            'vendor/crm/hosted',

            // Dev files
            'resources/js',
            'public/static',

            // Old files
            'app/Innoclapps',
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Indicates whether to restart the queue when finalizing the update or patch is applied.
    |---------------------------------------------------------------------------
    */

    'restart_queue' => true,

    /*
    |---------------------------------------------------------------------------
    | Indicates whether the patches should be automatically applied.
    |---------------------------------------------------------------------------
    */

    'auto_patch' => env('AUTO_PATCH', false),
];
