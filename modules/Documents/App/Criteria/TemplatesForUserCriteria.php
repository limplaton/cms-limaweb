<?php
 

namespace Modules\Documents\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;

class TemplatesForUserCriteria implements QueryCriteria
{
    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $model): void
    {
        $model->where(function ($query) {
            return $query->where('user_id', Auth::id())->orWhere('is_shared', true);
        });
    }
}
