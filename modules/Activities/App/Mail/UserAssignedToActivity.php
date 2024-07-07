<?php
 

namespace Modules\Activities\App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Resources\Activity as ResourceActivity;
use Modules\Core\App\Common\Placeholders\ActionButtonPlaceholder;
use Modules\Core\App\Common\Placeholders\PrivacyPolicyPlaceholder;
use Modules\Core\App\MailableTemplate\DefaultMailable;
use Modules\Core\App\Resource\ResourcePlaceholders;
use Modules\MailClient\App\Mail\MailableTemplate;
use Modules\Users\App\Models\User;
use Modules\Users\App\Placeholders\UserPlaceholder;

class UserAssignedToActivity extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new mailable template instance.
     */
    public function __construct(protected Activity $activity, protected User $assigneer)
    {
    }

    /**
     * Provide the defined mailable template placeholders
     */
    public function placeholders(): ResourcePlaceholders
    {
        return ResourcePlaceholders::make(new ResourceActivity, $this->activity ?? null)
            ->push([
                ActionButtonPlaceholder::make(fn () => $this->activity),
                PrivacyPolicyPlaceholder::make(),
                UserPlaceholder::make(fn () => $this->assigneer->name, 'assigneer')
                    ->description(__('activities::activity.mail_placeholders.assigneer')),
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
        return '<p>Hello {{ activity.user }}<br /></p>
                <p>You have been assigned to activity {{ activity.name }} by {{ assigneer }}<br /></p>
                <p>{{#action_button}}View Activity{{/action_button}}</p>';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return 'You are added as an owner of the activity {{ activity.title }}';
    }
}
