<?php
 

namespace Modules\Contacts\App\Observers;

use Modules\Contacts\App\Models\Contact;

class ContactObserver
{
    /**
     * Handle the Contact "deleting" event.
     */
    public function deleting(Contact $contact): void
    {
        if ($contact->isForceDeleting()) {
            $contact->purge();
        } else {
            $contact->guests()->delete();
        }
    }
}
