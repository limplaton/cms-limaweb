<?php
 

namespace Modules\Core\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\OAuthAccount;
use Modules\Users\App\Models\User;

class OAuthAccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the account.
     */
    public function view(User $user, OAuthAccount $account): bool
    {
        return (int) $account->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the account.
     */
    public function delete(User $user, OAuthAccount $account): bool
    {
        return (int) $user->id === (int) $account->user_id;
    }
}
