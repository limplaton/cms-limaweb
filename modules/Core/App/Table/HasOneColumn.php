<?php
 

namespace Modules\Core\App\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HasOneColumn extends RelationshipColumn
{
    /**
     * Initialize new HasOneColumn instance class.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->fillRowDataUsing(function (array &$row, Model $model) {
            $row[$this->attribute] = $this->toRowData($model->{$this->relationName});
        });
    }

    /**
     * Apply the order by query for the column
     */
    public function orderBy(Builder $query, string $direction): Builder
    {
        $relationInstance = $query->getModel()->{$this->relationName}();

        if (is_callable($this->orderByUsing)) {
            return call_user_func_array($this->orderByUsing, [$query, $direction, $this]);
        }

        $qualifiedRelationshipField = $relationInstance->qualifyColumn($this->relationField);

        return $query->orderBy(
            $relationInstance->getModel()->select($qualifiedRelationshipField)
                ->whereColumn($relationInstance->getQualifiedForeignKeyName(), $query->getModel()->getQualifiedKeyName())
                ->orderBy($qualifiedRelationshipField, $direction)
                ->limit(1),
            $direction
        );
    }
}
