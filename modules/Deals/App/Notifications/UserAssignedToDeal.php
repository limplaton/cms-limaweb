<?php
 

namespace Modules\Deals\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Deals\App\Mail\UserAssignedToDeal as AssignedToDealMailable;
use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Models\User;

class UserAssignedToDeal extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Deal $deal, protected User $assigneer)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): AssignedToDealMailable&MailableTemplate
    {
        return (new AssignedToDealMailable($this->deal, $this->assigneer))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'path' => $this->deal->path(),
            'lang' => [
                'key' => 'deals::deal.notifications.assigned',
                'attrs' => [
                    'user' => $this->assigneer->name,
                    'name' => $this->deal->displayName(),
                ],
            ],
        ];
    }
}
