<?php
 

namespace Modules\Users\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Users\App\Mail\UserMentioned as UserMentionedMailable;
use Modules\Users\App\Models\User;

class UserMentioned extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public string $mentionUrl, public User $mentioner)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): UserMentionedMailable&MailableTemplate
    {
        return (new UserMentionedMailable(
            $notifiable,
            $this->mentionUrl,
            $this->mentioner
        ))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'path' => $this->mentionUrl,
            'lang' => [
                'key' => 'users::user.notifications.user_mentioned',
                'attrs' => [
                    'name' => $this->mentioner->name,
                ],
            ],
        ];
    }
}
