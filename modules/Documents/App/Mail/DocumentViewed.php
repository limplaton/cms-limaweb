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

class DocumentViewed extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new mailable instance.
     */
    public function __construct(protected Document $document)
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
                <p>The document {{ document.title }} has been viewed by the customer<br /></p>
                <p>{{#action_button}}View Document{{/action_button}}</p>';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return '{{ document.title }} document has been viewed';
    }
}
