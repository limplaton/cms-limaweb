<?php
 

namespace Modules\Activities\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Activities\App\Mail\UserAssignedToActivity as UserAssignedToActivityMailable;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Users\App\Models\User;

class UserAssignedToActivity extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Activity $activity, protected User $assigneer)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): UserAssignedToActivityMailable&MailableTemplate
    {
        return (new UserAssignedToActivityMailable($this->activity, $this->assigneer))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'path' => $this->activity->path(),
            'lang' => [
                'key' => 'activities::activity.notifications.assigned',
                'attrs' => [
                    'user' => $this->assigneer->name,
                    'name' => $this->activity->displayName(),
                ],
            ],
        ];
    }
}
