<?php
 

namespace Modules\MailClient\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;

class EmailAccountMessagesForUserCriteria implements QueryCriteria
{
    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $model)
    {
        return $model->whereHas('account', function ($query) {
            $query->criteria(EmailAccountsForUserCriteria::class);
        });
    }
}
