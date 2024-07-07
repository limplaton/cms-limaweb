<?php
 

namespace Modules\Core\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\Dashboard;
use Modules\Users\App\Models\User;

class DashboardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the dashboard.
     */
    public function view(User $user, Dashboard $dashboard): bool
    {
        return (int) $user->id === (int) $dashboard->user_id;
    }

    /**
     * Determine whether the user can update the dashboards.
     */
    public function update(User $user, Dashboard $dashboard): bool
    {
        return (int) $user->id === (int) $dashboard->user_id;
    }

    /**
     * Determine whether the user can delete the dashboard.
     */
    public function delete(User $user, Dashboard $dashboard): bool
    {
        return (int) $user->id === (int) $dashboard->user_id;
    }
}
