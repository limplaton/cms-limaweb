<?php
 

namespace Modules\MailClient\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Modules\Users\App\Models\User;

class EmailAccountMessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the message.
     *
     * Used for message sync associations
     */
    public function update(User $user, EmailAccountMessage $message): bool
    {
        if ($message->account->isPersonal()) {
            return (int) $user->id === (int) $message->account->user_id;
        }

        if ($user->can('access shared inbox')) {
            return true;
        }

        return false;
    }
}
