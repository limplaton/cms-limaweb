<?php
 

namespace Modules\Core\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;
use Modules\Users\App\Models\User;

class VisibleModelsCriteria implements QueryCriteria
{
    /**
     * Create new VisibleModelsCriteria instance.
     */
    public function __construct(protected ?User $user = null)
    {
    }

    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $query): void
    {
        $query->visible($this->user ?: Auth::user());
    }
}
