<?php
 

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('inbox', function () {
    return true;
});
