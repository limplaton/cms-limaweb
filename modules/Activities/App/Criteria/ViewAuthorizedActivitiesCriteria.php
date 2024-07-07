<?php
 

namespace Modules\Activities\App\Criteria;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;
use Modules\Users\App\Criteria\QueriesByUserCriteria;
use Modules\Users\App\Models\User;

class ViewAuthorizedActivitiesCriteria implements QueryCriteria
{
    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $query): void
    {
        /** @var \Modules\Users\App\Models\User */
        $user = Auth::user();

        if ($user->can('view all activities')) {
            return;
        }

        $query->where(function ($query) use ($user) {
            $query->criteria(new QueriesByUserCriteria($user));

            if ($user->can('view attends and owned activities')) {
                $query->orWhereHas('guests', function ($query) use ($user) {
                    return $query->where('guestable_type', User::class)->where('guestable_id', $user->getKey());
                });

                if ($user->can('view team activities')) {
                    $this->whereTeamActivities($query, $user);
                }
            } elseif ($user->can('view team activities')) {
                $this->whereTeamActivities($query, $user);
            }
        });
    }

    /**
     * Apply a where for the given query to include team activities.
     */
    protected function whereTeamActivities(Builder $query, User $user): void
    {
        $query->orWhereHas('user.teams', function ($query) use ($user) {
            $query->where('teams.user_id', $user->getKey());
        });
    }
}
