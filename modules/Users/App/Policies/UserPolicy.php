<?php
 

namespace Modules\Users\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Users\App\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $currentUser): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $currentUser, User $user): bool
    {
        return true;
    }

    /**
     * Determine if the given user can create users.
     */
    public function create(User $currentUser): bool
    {
        // Only super admins
        return false;
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $currentUser, User $user): bool
    {
        // Only super admins
        return false;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $currentUser, User $user): bool
    {
        // Only super admins
        return false;
    }
}
