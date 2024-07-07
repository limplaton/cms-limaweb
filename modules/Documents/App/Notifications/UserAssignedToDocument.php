<?php
 

namespace Modules\Documents\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Documents\App\Mail\UserAssignedToDocument as UserAssignedToDocumentMailable;
use Modules\Documents\App\Models\Document;
use Modules\Users\App\Models\User;

class UserAssignedToDocument extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Document $document, protected User $assigneer)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): UserAssignedToDocumentMailable&MailableTemplate
    {
        return (new UserAssignedToDocumentMailable($this->document, $this->assigneer))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'path' => $this->document->path(),
            'lang' => [
                'key' => 'documents::document.notifications.assigned',
                'attrs' => [
                    'user' => $this->assigneer->name,
                    'title' => $this->document->displayName(),
                ],
            ],
        ];
    }
}
