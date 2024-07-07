<?php
 

namespace Modules\Activities\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Activities\App\Contracts\Attendeeable;
use Modules\Activities\App\Mail\UserAttendsToActivity as UserAttendsToActivityMailable;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;

class UserAttendsToActivity extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Attendeeable $guestable, protected Activity $activity)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): UserAttendsToActivityMailable&MailableTemplate
    {
        return (new UserAttendsToActivityMailable($this->guestable, $this->activity))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'path' => $this->activity->path(),
            'lang' => [
                'key' => 'activities::activity.notifications.added_as_guest',
                'attrs' => [],
            ],
        ];
    }
}
