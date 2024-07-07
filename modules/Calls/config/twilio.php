<?php
 

return [
    /*
    |--------------------------------------------------------------------------
    | Twilio configuration
    |--------------------------------------------------------------------------
    */
    'applicationSid' => env('TWILIO_APP_SID'),
    'accountSid' => env('TWILIO_ACCOUNT_SID'),
    'authToken' => env('TWILIO_AUTH_TOKEN'),
    'number' => env('TWILIO_NUMBER'),
];
