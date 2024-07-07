<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;

class OwnEventsCriteria implements QueryCriteria
{
    public function apply(Builder $model): void
    {
        if (Auth::user()->cant('view all events')) {
            $model->where('user_id', Auth::id());
        }
    }
}
