<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server/PHP Requirements and Functions
    |--------------------------------------------------------------------------
    |
    */
    'core' => [
        'minPhpVersion' => \DetachedHelper::MINIMUM_PHP_VERSION,
    ],

    'requirements' => [
        'php' => [
            'bcmath',
            'ctype',
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'cURL',
            'iconv',
            'gd',
            'fileinfo',
            'dom',
        ],

        'apache' => [
            'mod_rewrite',
        ],

        'functions' => [
            'symlink',
            'tmpfile',
            'file', // dompdf
            'ignore_user_abort',
            'fpassthru',
            'highlight_file',
        ],

        'recommended' => [
            'php' => [
                'imap',
                'zip',
            ],

            'functions' => [
                'proc_open',
                'proc_close',
                'proc_get_status',
                'proc_terminate',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Folders Permissions
    |--------------------------------------------------------------------------
    |
    | These are the default Laravel folders permissions.
    |
    */
    'permissions' => [
        'storage/app/' => '755',
        'storage/framework/' => '755',
        'storage/logs/' => '755',
        'bootstrap/cache/' => '755',
    ],
];
