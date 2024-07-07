<?php
 

namespace Modules\Contacts\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contacts\App\Mail\UserAssignedToContact as AssignedToContactMailable;
use Modules\Contacts\App\Models\Contact;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Users\App\Models\User;

class UserAssignedToContact extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Contact $contact, protected User $assigneer)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): AssignedToContactMailable&MailableTemplate
    {
        return (new AssignedToContactMailable($this->contact, $this->assigneer))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'path' => $this->contact->path(),
            'lang' => [
                'key' => 'contacts::contact.notifications.assigned',
                'attrs' => [
                    'user' => $this->assigneer->name,
                    'name' => $this->contact->displayName(),
                ],
            ],
        ];
    }
}
