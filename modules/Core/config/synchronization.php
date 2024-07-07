<?php
 

return [
    /*
    |--------------------------------------------------------------------------
    | Synchronization interval definition
    |--------------------------------------------------------------------------
    |
    | For periodic synchronization like Google, the events by default
    | are synchronized every 3 minutes, the interval can be defined below in cron style.
    */
    'interval' => env('SYNC_INTERVAL', '*/3 * * * *'),
];
