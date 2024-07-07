<?php
 

namespace Modules\Core\App\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Criteria\QueryCriteria;

class RelatedCriteria implements QueryCriteria
{
    /**
     * Apply the criteria for the given query.
     */
    public function apply(Builder $base): void
    {
        $base->where(function ($query) use ($base) {
            $resource = $base->getModel()->resource();

            $i = 0;
            foreach ($resource->associateableResources() as $relation => $resource) {
                if ($criteria = $resource->viewAuthorizedRecordsCriteria()) {
                    $query->{$i === 0 ? 'whereHas' : 'orWhereHas'}($relation, function ($query) use ($criteria) {
                        (new $criteria)->apply($query);
                    });
                }
                $i++;
            }

            if (method_exists($base, 'user')) {
                $query->orWhere($base->user()->getForeignKeyName(), auth()->id());
            }
        });
    }
}
