<?php
 

namespace Modules\Contacts\App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Contacts\App\Mail\UserAssignedToCompany as AssignedToCompanyMailable;
use Modules\Contacts\App\Models\Company;
use Modules\Core\App\MailableTemplate\MailableTemplate;
use Modules\Core\App\Notifications\Notification;
use Modules\Users\App\Models\User;

class UserAssignedToCompany extends Notification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Company $company, protected User $assigneer)
    {
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): AssignedToCompanyMailable&MailableTemplate
    {
        return (new AssignedToCompanyMailable($this->company, $this->assigneer))->to($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'path' => $this->company->path(),
            'lang' => [
                'key' => 'contacts::company.notifications.assigned',
                'attrs' => [
                    'user' => $this->assigneer->name,
                    'name' => $this->company->displayName(),
                ],
            ],
        ];
    }
}
