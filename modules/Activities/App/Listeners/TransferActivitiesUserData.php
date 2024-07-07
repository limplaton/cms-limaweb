<?php
 

namespace Modules\Activities\App\Listeners;

use Modules\Activities\App\Models\Activity;
use Modules\Users\App\Events\TransferringUserData;

class TransferActivitiesUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        $event->fromUser->guests()->delete();
        $event->fromUser->connectedCalendars->each->delete();

        Activity::withTrashed()->where('created_by', $event->fromUserId)->update(['created_by' => $event->toUserId]);
        Activity::withTrashed()->where('user_id', $event->fromUserId)->update(['user_id' => $event->toUserId]);
    }
}
