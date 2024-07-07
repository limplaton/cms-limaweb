<?php
 

namespace Modules\Core\App\Filters;

interface CountableRelation
{
    /**
     * Indicates that the filter will count the values of the relation
     *
     * @param  string|null  $relationName
     * @return \Modules\Core\App\Filters\Filter
     */
    public function countableRelation($relationName = null);

    /**
     * Get the countable relation name
     *
     * @return string|null
     */
    public function getCountableRelation();
}
