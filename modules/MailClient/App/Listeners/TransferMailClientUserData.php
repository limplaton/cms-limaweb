<?php
 

namespace Modules\MailClient\App\Listeners;

use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\PredefinedMailTemplate;
use Modules\Users\App\Events\TransferringUserData;

class TransferMailClientUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        $this->emailAccounts($event->toUserId, $event->fromUserId);
        $this->predefinedMailTemplates($event->toUserId, $event->fromUserId);
    }

    /**
     * Transfer accounts created by.
     *
     * Personal accounts are deleted, here only shared are transfered.
     */
    public function emailAccounts($toUserId, $fromUserId): void
    {
        EmailAccount::where('created_by', $fromUserId)->update(['created_by' => $toUserId]);
    }

    /**
     * Transfer shared predefined mail templates.
     */
    public function predefinedMailTemplates($toUserId, $fromUserId): void
    {
        // Purge user non shared mail templates.
        PredefinedMailTemplate::where('user_id', $fromUserId)->where('is_shared', 0)->delete();

        // Transfer shared mail templates to the selected user.
        PredefinedMailTemplate::where('user_id', $fromUserId)
            ->shared()
            ->update(['user_id' => $toUserId]);
    }
}
