<?php
 

namespace Modules\Calls\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Modules\Calls\App\Models\Call;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Users\App\Models\User;

class CallPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any calls.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the call.
     */
    public function view(User $user, Call $call): bool
    {
        return (int) $user->id === (int) $call->user_id;
    }

    /**
     * Determine if the given user can create calls.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the call.
     */
    public function update(User $user, Call $call): bool
    {
        return (int) $user->id === (int) $call->user_id;
    }

    /**
     * Determine whether the user can delete the call.
     */
    public function delete(User $user, Call $call): bool
    {
        return (int) $user->id === (int) $call->user_id;
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
                ->whereHas('calls', fn (Builder $query) => $query->where('id', $request->route()->parameter('resourceId')))
                ->find($request->get('via_resource_id'));

            if ($related) {
                return Gate::allows('view', $related);
            }
        }

        return null;
    }
}
