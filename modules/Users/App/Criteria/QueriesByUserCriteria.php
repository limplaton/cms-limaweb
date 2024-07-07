<?php
 

namespace Modules\Users\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;
use Modules\Users\App\Models\User;

class QueriesByUserCriteria implements QueryCriteria
{
    /**
     * Initialze QueriesByUserCriteria class.
     */
    public function __construct(protected null|User|int $user = null, protected string $column = 'user_id')
    {
    }

    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $model): void
    {
        $model->where($this->column, $this->getUser()->getKey());
    }

    /**
     * Determine the user.
     */
    protected function getUser(): User
    {
        $user = $this->user;

        if ($user instanceof User) {
            return $user;
        }

        if (is_int($user)) {
            return User::find($user);
        }

        return Auth::user();
    }
}
