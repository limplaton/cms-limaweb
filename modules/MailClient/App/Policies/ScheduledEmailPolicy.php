<?php
 

namespace Modules\MailClient\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\MailClient\App\Models\ScheduledEmail;
use Modules\Users\App\Models\User;

class ScheduledEmailPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the scheduled email.
     */
    public function delete(User $user, ScheduledEmail $message): bool
    {
        return (int) $user->id === (int) $message->user_id;
    }
}
