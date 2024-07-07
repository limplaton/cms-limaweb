<?php
 

namespace Modules\Contacts\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Contacts\App\Models\Industry;
use Modules\Users\App\Models\User;

class IndustryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any industries.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the industry.
     */
    public function view(User $user, Industry $industry): bool
    {
        return true;
    }

    /**
     * Determine if the given user can create industry.
     */
    public function create(User $user): bool
    {
        // Only super admins
        return false;
    }

    /**
     * Determine whether the user can update the industry.
     */
    public function update(User $user, Industry $industry): bool
    {
        // Only super admins
        return false;
    }

    /**
     * Determine whether the user can delete the industry.
     */
    public function delete(User $user, Industry $industry): bool
    {
        // Only super admins
        return false;
    }
}
