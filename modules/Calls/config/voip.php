<?php
 

return [
    'client' => env('VOIP_CLIENT'),
    // Route names
    'endpoints' => [
        'call' => 'voip.call',
        'events' => 'voip.events',
    ],
];
