<?php
 

namespace Modules\Documents\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Documents\App\Mail\SignerSignedDocument as SignerSignedDocumentMailable;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentSigner;

class SignerSignedDocument extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Document $document, protected DocumentSigner $signer)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): SignerSignedDocumentMailable&MailableTemplate
    {
        return (new SignerSignedDocumentMailable($this->document, $this->signer))->to($notifiable);
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
                'key' => 'documents::document.notifications.signed',
                'attrs' => [
                    'title' => $this->document->title,
                ],
            ],
        ];
    }
}
