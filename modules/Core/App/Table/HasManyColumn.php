<?php
 

namespace Modules\Core\App\Table;

use Illuminate\Database\Eloquent\Model;

class HasManyColumn extends RelationshipColumn
{
    /**
     * HasManyColumn is not sortable by default.
     */
    public bool $sortable = false;

    /**
     * Initialize new HasManyColumn instance class.
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());

        $this->fillRowDataUsing(function (array &$row, Model $model) {
            $row[$this->attribute] = $model->{$this->relationName}->map(function (Model $relation) {
                return $this->toRowData($relation);
            });
        });
    }
}