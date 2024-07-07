<?php
 

namespace Modules\Contacts\App\Listeners;

use Modules\Contacts\App\Models\Company;
use Modules\Contacts\App\Models\Contact;
use Modules\Users\App\Events\TransferringUserData;

class TransferContactsUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        $this->contacts($event->toUserId, $event->fromUserId);
        $this->companies($event->toUserId, $event->fromUserId);
    }

    /**
     * Transfer contacts.
     */
    public function contacts($toUserId, $fromUserID): void
    {
        Contact::withTrashed()->where('created_by', $fromUserID)->update(['created_by' => $toUserId]);
        Contact::withTrashed()->where('user_id', $fromUserID)->update(['user_id' => $toUserId]);
    }

    /**
     * Transfer companies.
     */
    public function companies($toUserId, $fromUserID): void
    {
        Company::withTrashed()->where('created_by', $fromUserID)->update(['created_by' => $toUserId]);
        Company::withTrashed()->where('user_id', $fromUserID)->update(['user_id' => $toUserId]);
    }
}
