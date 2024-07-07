<?php
 

namespace Modules\Core\App\Contracts\Criteria;

use Illuminate\Database\Eloquent\Builder;

interface QueryCriteria
{
    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $builder);
}
