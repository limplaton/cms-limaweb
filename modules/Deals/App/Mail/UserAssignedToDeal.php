<?php
 

namespace Modules\Deals\App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\Common\Placeholders\ActionButtonPlaceholder;
use Modules\Core\App\Common\Placeholders\PrivacyPolicyPlaceholder;
use Modules\Core\App\MailableTemplate\DefaultMailable;
use Modules\Core\App\Resource\ResourcePlaceholders;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Resources\Deal as ResourceDeal;
use Modules\MailClient\App\Mail\MailableTemplate;
use Modules\Users\App\Models\User;
use Modules\Users\App\Placeholders\UserPlaceholder;

class UserAssignedToDeal extends MailableTemplate implements ShouldQueue
{
    /**
     * Create a new mailable template instance.
     */
    public function __construct(protected Deal $deal, protected User $assigneer)
    {
    }

    /**
     * Provide the defined mailable template placeholders
     */
    public function placeholders(): ResourcePlaceholders
    {
        return ResourcePlaceholders::make(new ResourceDeal, $this->deal ?? null)
            ->push([
                ActionButtonPlaceholder::make(fn () => $this->deal),
                PrivacyPolicyPlaceholder::make(),
                UserPlaceholder::make(fn () => $this->assigneer->name, 'assigneer')
                    ->description(__('deals::deal.mail_placeholders.assigneer')),
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
        return '<p>Hello {{ deal.user }}<br /></p>
                <p>You have been assigned to a deal {{ deal.name }} by {{ assigneer }}<br /></p>
                <p>{{#action_button}}View Deal{{/action_button}}</p>';
    }

    /**
     * Provides the mail template default subject
     */
    public static function defaultSubject(): string
    {
        return 'You are added as an owner of the deal {{ deal.name }}';
    }
}
