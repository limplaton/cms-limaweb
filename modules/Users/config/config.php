<?php
 

return [
    'name' => 'Users',
    /*
    |--------------------------------------------------------------------------
    | User invitation config
    |--------------------------------------------------------------------------
    |
    */
    'invitation' => [
        'expires_after' => env('USER_INVITATION_EXPIRES_AFTER', 3), // in days
    ],
];
