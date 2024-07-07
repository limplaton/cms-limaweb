<?php
 

namespace Modules\Core\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\Filter;
use Modules\Users\App\Models\User;

class FilterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the filter.
     */
    public function update(User $user, Filter $filter): bool
    {
        return (int) $filter->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the filter.
     */
    public function delete(User $user, Filter $filter): bool
    {
        return (int) $filter->user_id === (int) $user->id;
    }
}
