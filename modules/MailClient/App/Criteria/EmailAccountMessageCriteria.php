<?php
 

namespace Modules\MailClient\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;

class EmailAccountMessageCriteria implements QueryCriteria
{
    /**
     * Initialize EmailAccountMessageCriteria class.
     */
    public function __construct(protected int|string $accountId, protected int|string $folderId)
    {
    }

    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $query): void
    {
        $query->where('email_account_id', $this->accountId)
            ->whereHas('folders', function ($query) {
                return $query->where('folder_id', $this->folderId);
            });
    }
}
