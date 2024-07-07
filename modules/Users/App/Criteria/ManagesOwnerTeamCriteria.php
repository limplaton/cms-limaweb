<?php
 

namespace Modules\Users\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;
use Modules\Users\App\Models\User;

class ManagesOwnerTeamCriteria implements QueryCriteria
{
    /**
     * Initialize new ManagesOwnerTeamCriteria instance
     */
    public function __construct(protected User $user, protected string $relation = 'user')
    {
    }

    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $model): void
    {
        $model->whereHas($this->relation.'.teams', function ($query) {
            $query->where('teams.user_id', $this->user->getKey());
        });
    }
}
