<?php
 

namespace Modules\Deals\App\Listeners;

use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Events\TransferringUserData;

class TransferDealsUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        Deal::withTrashed()->where('created_by', $event->fromUserId)->update(['created_by' => $event->toUserId]);
        Deal::withTrashed()->where('user_id', $event->fromUserId)->update(['user_id' => $event->toUserId]);
    }
}
