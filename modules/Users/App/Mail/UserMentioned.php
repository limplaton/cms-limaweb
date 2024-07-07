<?php
 

namespace Modules\Users\App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\Common\Placeholders\ActionButtonPlaceholder;
use Modules\Core\App\Common\Placeholders\Placeholders as BasePlaceholders;
use Modules\Core\App\Common\Placeholders\PrivacyPolicyPlaceholder;
use Modules\Core\App\Common\Placeholders\UrlPlaceholder;
use Modules\Core\App\MailableTemplate\DefaultMailable;
use Modules\MailClient\App\Mail\MailableTemplate;
use Modules\Users\App\Models\User;
use Modules\Users\App\Placeholders\UserPlaceholder;

class UserMentioned extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new mailable template instance.
     */
    public function __construct(protected User $mentioned, protected string $mentionUrl, protected User $mentioner)
    {
    }

    /**
     * Provide the defined mailable template placeholders
     */
    public function placeholders(): BasePlaceholders
    {
        return new BasePlaceholders([
            UserPlaceholder::make(fn () => $this->mentioned->name, 'mentioned_user')
                ->description(__('core::mail_template.placeholders.mentioned_user')),

            UserPlaceholder::make(fn () => $this->mentioner->name)
                ->description(__('core::mail_template.placeholders.user_that_mentions')),

            UrlPlaceholder::make(fn () => $this->mentionUrl, 'url')
                ->description(__('core::mail_template.placeholders.mention_url')),

            ActionButtonPlaceholder::make(fn () => $this->mentionUrl),

            PrivacyPolicyPlaceholder::make(),
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
        return '<p>Hello {{ mentioned_user }}<br /></p>
                <p>{{ user }} mentioned you.<br /></p>
                <p>{{#action_button}}View Record{{/action_button}}</p>';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return 'You Were Mentioned by {{ user }}';
    }
}
