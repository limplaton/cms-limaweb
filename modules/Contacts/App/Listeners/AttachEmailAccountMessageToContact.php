<?php
 

namespace Modules\Contacts\App\Listeners;

use Modules\Contacts\App\Models\Contact;
use Modules\MailClient\App\Events\EmailAccountMessageCreated;

class AttachEmailAccountMessageToContact
{
    /**
     * When a message is created, try to associate the message with the actual contact if exists in database
     */
    public function handle(EmailAccountMessageCreated $event): void
    {
        $message = $event->message;

        $emails = array_unique(array_filter([
            $message->from?->address,
            ...$message->to->pluck('address')->all(),
            ...$message->cc->pluck('address')->all(),
            ...$message->bcc->pluck('address')->all(),
        ]));

        if (count($emails) === 0) {
            return;
        }

        $contacts = Contact::whereIn('email', $emails)->get('id');

        foreach ($contacts as $contact) {
            $contact->emails()->syncWithoutDetaching($message->id);
        }
    }
}
