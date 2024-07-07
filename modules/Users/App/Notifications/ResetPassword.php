<?php
 

namespace Modules\Users\App\Notifications;

use Modules\Core\App\Contracts\HasNotificationsSettings;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Users\App\Mail\ResetPassword as ResetPasswordMailable;

class ResetPassword extends Notification
{
    /**
     * Create a notification instance.
     */
    public function __construct(public string $token)
    {
    }

    /**
     * Get the notification's channels.
     */
    public function via(HasNotificationsSettings $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): ResetPasswordMailable&MailableTemplate
    {
        return (new ResetPasswordMailable($this->resetUrl($notifiable)))->to($notifiable);
    }

    /**
     * Get the reset URL for the given notifiable.
     */
    protected function resetUrl(object $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    /**
     * Define whether the notification is user-configurable
     */
    public static function configurable(): bool
    {
        return false;
    }
}
