<?php
 

namespace Modules\Documents\App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\Common\Placeholders\ActionButtonPlaceholder;
use Modules\Core\App\Common\Placeholders\PrivacyPolicyPlaceholder;
use Modules\Core\App\MailableTemplate\DefaultMailable;
use Modules\Core\App\Resource\ResourcePlaceholders;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Resources\Document as ResourceDocument;
use Modules\MailClient\App\Mail\MailableTemplate;
use Modules\Users\App\Models\User;
use Modules\Users\App\Placeholders\UserPlaceholder;

class UserAssignedToDocument extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new mailable template instance.
     */
    public function __construct(protected Document $document, protected User $assigneer)
    {
    }

    /**
     * Provide the defined mailable template placeholders
     */
    public function placeholders(): ResourcePlaceholders
    {
        return ResourcePlaceholders::make(new ResourceDocument, $this->document ?? null)
            ->push([
                ActionButtonPlaceholder::make(fn () => $this->document),
                PrivacyPolicyPlaceholder::make(),
                UserPlaceholder::make(fn () => $this->assigneer->name, 'assigneer')
                    ->description(__('documents::document.mail_placeholders.assigneer')),
            ])
            ->withUrlPlaceholder();
    }

    /**
     * Provides the mail template default configuration
     */
    public static function default(): DefaultMailable
    {
        return new DefaultMailable(static::defaultHtmlTemplate(), static::defaultSubject());
    }

    /**
     * Provides the mail template default message
     */
    public static function defaultHtmlTemplate(): string
    {
        return '<p>Hello {{ document.user }}<br /></p>
                <p>You have been assigned to a document {{ document.title }} by {{ assigneer }}<br /></p>
                <p>{{#action_button}}View Document{{/action_button}}</p>';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return 'You are added as an owner of the document {{ document.title }}';
    }
}
