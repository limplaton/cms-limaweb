<?php
 

namespace Modules\Deals\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Deals\App\Models\Deal;
use Modules\Users\App\Models\User;

class DealPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any deals.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the deal.
     */
    public function view(User $user, Deal $deal): bool
    {
        if ($user->can('view all deals')) {
            return true;
        }

        if ((int) $deal->user_id === (int) $user->id) {
            return true;
        }

        if ($deal->user_id && $user->can('view team deals')) {
            return $user->managesAnyTeamsOf($deal->user_id);
        }

        return false;
    }

    /**
     * Determine if the given user can create deals.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the deal.
     */
    public function update(User $user, Deal $deal): bool
    {
        if ($user->can('edit all deals')) {
            return true;
        }

        if ($user->can('edit own deals') && (int) $user->id === (int) $deal->user_id) {
            return true;
        }

        if ($deal->user_id && $user->can('edit team deals') && $user->managesAnyTeamsOf($deal->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the deal.
     */
    public function delete(User $user, Deal $deal): bool
    {
        if ($user->can('delete any deal')) {
            return true;
        }

        if ($user->can('delete own deals') && (int) $user->id === (int) $deal->user_id) {
            return true;
        }

        if ($deal->user_id && $user->can('delete team deals') && $user->managesAnyTeamsOf($deal->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete deals.
     */
    public function bulkDelete(User $user, ?Deal $deal = null)
    {
        if (! $deal) {
            return $user->can('bulk delete deals');
        }

        if ($deal && $user->can('bulk delete deals')) {
            return $this->delete($user, $deal);
        }

        return false;
    }

    /**
     * Determine whether the user can export deals.
     */
    public function export(User $user): bool
    {
        return $user->can('export deals');
    }
}
