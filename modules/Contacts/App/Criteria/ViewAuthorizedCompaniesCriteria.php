<?php
 

namespace Modules\Contacts\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;
use Modules\Users\App\Criteria\QueriesByUserCriteria;

class ViewAuthorizedCompaniesCriteria implements QueryCriteria
{
    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $query): void
    {
        /** @var \Modules\Users\App\Models\User */
        $user = Auth::user();

        if ($user->can('view all companies')) {
            return;
        }

        $query->where(function ($query) use ($user) {
            $query->criteria(new QueriesByUserCriteria($user));

            if ($user->can('view team companies')) {
                $query->orWhereHas('user.teams', function ($query) use ($user) {
                    $query->where('teams.user_id', $user->getKey());
                });
            }
        });
    }
}
