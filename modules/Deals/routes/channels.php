<?php
 

use Illuminate\Support\Facades\Broadcast;
use Modules\Deals\App\Models\Deal;

Broadcast::channel('Modules.Deals.App.Models.Deal.{dealId}', function ($user, $dealId) {
    return $user->can('view', Deal::findOrFail($dealId));
});
