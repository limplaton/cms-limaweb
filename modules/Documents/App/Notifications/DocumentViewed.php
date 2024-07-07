<?php
 

namespace Modules\Documents\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Documents\App\Mail\DocumentViewed as DocumentViewedMailable;
use Modules\Documents\App\Models\Document;

class DocumentViewed extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Document $document)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): DocumentViewedMailable&MailableTemplate
    {
        return (new DocumentViewedMailable($this->document))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'path' => $this->document->path(),
            'lang' => [
                'key' => 'documents::document.notifications.viewed',
                'attrs' => [
                    'title' => $this->document->title,
                ],
            ],
        ];
    }
}
