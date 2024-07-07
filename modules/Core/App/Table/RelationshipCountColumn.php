<?php
 

namespace Modules\Core\App\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RelationshipCountColumn extends Column
{
    /**
     * The relationshiop name to perform count to.
     */
    public string $relationshipName;

    /**
     * Initialize new RelationshipCountColumn instance.
     */
    public function __construct(string $name, ?string $label = null, ?string $attribute = null)
    {
        parent::__construct($attribute ?: Str::snake($name).'_count', $label);

        $this->relationshipName = $name;

        $this->centered();
    }

    /**
     * Apply the order by query for the column
     */
    public function orderBy(Builder $query, string $direction): Builder
    {
        return $query->orderBy($this->attribute, $direction);
    }
}
