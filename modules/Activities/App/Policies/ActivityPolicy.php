<?php
 

namespace Modules\Activities\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Users\App\Models\User;

class ActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activities.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the activity.
     */
    public function view(User $user, Activity $activity): bool
    {
        if ($user->can('view all activities')) {
            return true;
        }

        if ($user->can('view attends and owned activities')) {
            return (int) $user->id === (int) $activity->user_id ||
                $activity->hasGuest($user) ||
                ($user->can('view team activities') && $user->managesAnyTeamsOf($activity->user_id));
        }

        if ((int) $user->id === (int) $activity->user_id) {
            return true;
        }

        if ($user->can('view team activities')) {
            return $user->managesAnyTeamsOf($activity->user_id);
        }

        return false;
    }

    /**
     * Determine if the given user can create activities.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the activity.
     */
    public function update(User $user, Activity $activity): bool
    {
        if ($user->can('edit all activities')) {
            return true;
        }

        if ($user->can('edit own activities') && (int) $user->id === (int) $activity->user_id) {
            return true;
        }

        if ($user->can('edit team activities') && $user->managesAnyTeamsOf($activity->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the activity.
     */
    public function delete(User $user, Activity $activity): bool
    {
        if ($user->can('delete any activity')) {
            return true;
        }

        if ($user->can('delete own activities') && (int) $user->id === (int) $activity->user_id) {
            return true;
        }

        if ($user->can('delete team activities') && $user->managesAnyTeamsOf($activity->user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user bulk delete activities.
     */
    public function bulkDelete(User $user, ?Activity $activity = null)
    {
        if (! $activity) {
            return $user->can('bulk delete activities');
        }

        if ($activity && $user->can('bulk delete activities')) {
            return $this->delete($user, $activity);
        }

        return false;
    }

    /**
     * Determine whether the user can export activities.
     */
    public function export(User $user): bool
    {
        return $user->can('export activities');
    }

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        $request = app(ResourceRequest::class);

        if ($ability === 'view' && $request->viaResource()) {
            $related = $request
                ->findResource($request->get('via_resource'))
                ->newQuery()
                ->whereHas('activities', fn (Builder $query) => $query->where('id', $request->route()->parameter('resourceId')))
                ->find($request->get('via_resource_id'));

            if ($related) {
                return Gate::allows('view', $related);
            }
        }

        return null;
    }
}
