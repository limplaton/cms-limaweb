<?php
 

namespace Modules\Notes\App\Listeners;

use Modules\Notes\App\Models\Note;
use Modules\Users\App\Events\TransferringUserData;

class TransferNotesUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        Note::where('user_id', $event->fromUserId)->update(['user_id' => $event->toUserId]);
    }
}
