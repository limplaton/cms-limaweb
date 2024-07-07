<?php
 

namespace Modules\Brands\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Brands\App\Models\Brand;
use Modules\Users\App\Models\User;

class BrandPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the brand.
     */
    public function view(User $user, Brand $brand): bool
    {
        return $brand->isVisible($user);
    }

    /**
     * Determine if the given user can create brand.
     */
    public function create(User $user): bool
    {
        // Only super admins
        return false;
    }

    /**
     * Determine whether the user can update the brand.
     */
    public function update(User $user, Brand $brand): bool
    {
        // Only super admins
        return false;
    }

    /**
     * Determine whether the user can delete the brand.
     */
    public function delete(User $user, Brand $brand): bool
    {
        // Only super admins
        return false;
    }
}
