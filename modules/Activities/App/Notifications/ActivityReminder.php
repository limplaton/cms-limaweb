<?php
 

namespace Modules\Activities\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Activities\App\Mail\ActivityReminder as ReminderMailable;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Core\App\Support\Carbon;

class ActivityReminder extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Activity $activity)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): ReminderMailable&MailableTemplate
    {
        return (new ReminderMailable($this->activity))->to($notifiable);
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
                'key' => 'activities::activity.notifications.due',
                'attrs' => [
                    'activity' => $this->activity->title,
                    'date' => $this->activity->due_time ?
                     Carbon::parse($this->activity->full_due_date)->formatDateTimeForUser($this->activity->user) :
                     Carbon::parse($this->activity->due_date)->formatDateForUser($this->activity->user),
                ],
            ],
        ];
    }
}
