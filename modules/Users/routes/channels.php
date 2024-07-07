<?php
 

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('Modules.Users.App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
