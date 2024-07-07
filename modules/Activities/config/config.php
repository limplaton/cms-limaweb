<?php
 

return [
    'name' => 'Activities',

    /*
    |--------------------------------------------------------------------------
    | Application defaults config
    |--------------------------------------------------------------------------
    | Here you can specify defaults configurations that the application
    | uses when configuring specific option e.q. creating a follow up task
    | automatically uses the configured hour and minutes.
    |
    */
    'defaults' => [
        'hour' => env('PREFERRED_DEFAULT_HOUR', 8),
        'minutes' => env('PREFERRED_DEFAULT_MINUTES', 0),
    ],
];
