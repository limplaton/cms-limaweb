<?php
 

namespace Modules\Core\App\Filters;

use Illuminate\Support\Str;

class Number extends Filter implements CountableRelation
{
    /**
     * The relation that the count is performed on
     *
     * @var string|null
     */
    public $countableRelation;

    /**
     * Indicates that the filter will count the val ues
     *
     * @param  string|null  $relationName
     * @return \Modules\Core\App\Filters\Filter
     */
    public function countableRelation($relationName = null)
    {
        $this->countableRelation = $relationName ?? lcfirst(Str::studly($this->field()));
        $operators = $this->getOperators();

        // between and not_between are not supported at this time.
        unset($operators[array_search('between', $operators)], $operators[array_search('not_between', $operators)]);

        $this->operators($operators);

        return $this;
    }

    /**
     * Get the countable relation name
     *
     * @return string|null
     */
    public function getCountableRelation()
    {
        return $this->countableRelation;
    }

    /**
     * Defines a filter type
     */
    public function type(): string
    {
        return 'number';
    }
}
