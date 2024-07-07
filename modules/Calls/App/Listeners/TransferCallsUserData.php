<?php
 

namespace Modules\Calls\App\Listeners;

use Modules\Calls\App\Models\Call;
use Modules\Users\App\Events\TransferringUserData;

class TransferCallsUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        Call::where('user_id', $event->fromUserId)->update(['user_id' => $event->toUserId]);
    }
}
