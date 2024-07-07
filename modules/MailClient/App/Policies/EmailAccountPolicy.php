<?php
 

namespace Modules\MailClient\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\Users\App\Models\User;

class EmailAccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the email account.
     */
    public function view(User $user, EmailAccount $account): bool
    {
        if ($account->isShared()) {
            return $user->can('access shared inbox');
        }

        return (int) $user->id === (int) $account->user_id;
    }

    /**
     * Determine whether the user can update the email account.
     */
    public function update(User $user, EmailAccount $account): bool
    {
        return $this->authorizeUpdateAndDelete($user, $account);
    }

    /**
     * Determine whether the user can delete the email account.
     */
    public function delete(User $user, EmailAccount $account): bool
    {
        // We check if the account not requires auth before deleting because
        // the user must re-authenticate the account in order to delete
        // This will allow in the observer, to revoke the access token
        // so if in case the next time the user want to re-connect the account to
        //return the refresh token as the refresh token is returned only on the first request
        if ($account->requires_auth) {
            return false;
        }

        return $this->authorizeUpdateAndDelete($user, $account);
    }

    /**
     * General account check
     */
    protected function authorizeUpdateAndDelete($user, $account): bool
    {
        // Not needed because of before policy authorization?
        if ($account->isShared()) {
            return $user->isSuperAdmin();
        }

        return (int) $user->id === (int) $account->user_id;
    }
}
