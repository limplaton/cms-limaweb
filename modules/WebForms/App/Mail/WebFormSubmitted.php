<?php
 

namespace Modules\WebForms\App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\Common\Placeholders\GenericPlaceholder;
use Modules\Core\App\Common\Placeholders\Placeholders as BasePlaceholders;
use Modules\Core\App\MailableTemplate\DefaultMailable;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\WebForms\App\Models\WebForm;
use Modules\WebForms\App\Services\FormSubmission;

class WebFormSubmitted extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new message instance.
     */
    public function __construct(public WebForm $form, public FormSubmission $submission)
    {
    }

    /**
     * Provide the defined mailable template placeholders
     */
    public function placeholders(): BasePlaceholders
    {
        return new BasePlaceholders([
            GenericPlaceholder::make('form.title', fn () => $this->form->title),
            GenericPlaceholder::make('payload', fn () => (string) $this->submission)
                ->withStartInterpolation('{{{')
                ->withEndInterpolation('}}}'),
        ]);
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
        return '<p>There is new submission via the {{ form.title }} web form.<br /><br /></p>
                <p>{{{ payload }}}</p>';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return 'New submission on {{ form.title }} form';
    }
}
