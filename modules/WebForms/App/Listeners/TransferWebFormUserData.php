<?php
 

namespace Modules\WebForms\App\Listeners;

use Modules\Users\App\Events\TransferringUserData;
use Modules\WebForms\App\Models\WebForm;

class TransferWebFormUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        WebForm::where('created_by', $event->fromUserId)->update(['created_by' => $event->toUserId]);
        WebForm::where('user_id', $event->fromUserId)->update(['user_id' => $event->toUserId]);
    }
}
