<?php
 

namespace Modules\Core\App\Table;

use Illuminate\Support\Str;

abstract class RelationshipColumn extends Column
{
    /**
     * The relationship name.
     */
    public string $relationName;

    /**
     * The relation field.
     */
    public string $relationField;

    /**
     * Initialize new RelationshipColumn instance.
     */
    public function __construct(string $relationName, string $relationField, ?string $label = null, ?string $attribute = null)
    {
        $attribute = $attribute ?: Str::snake($relationName);

        parent::__construct($attribute, $label);

        $this->relationName = $relationName;
        $this->relationField = $relationField;
    }

    /**
     * A helper function to convert the given related model to common row data format.
     */
    public function toRowData($model, array $extra = []): ?array
    {
        return $model ? array_merge([
            'id' => $model->getKey(),
            $this->relationField => $model->{$this->relationField},
        ], $model->only($this->appends), $extra) : null;
    }

    /**
     * Add relations to eager load for the column relation.
     */
    public function with(array|string $with): static
    {
        $this->with = array_merge(
            $this->with,
            $this->prefixEagerLoadedWithRelationName((array) $with)
        );

        return $this;
    }

    /**
     * Prefix the column eager loaded relationship with the actual relation name.
     */
    protected function prefixEagerLoadedWithRelationName(array $with): array
    {
        foreach ($with as $key => $value) {
            if (is_int($key) && ! str_starts_with($this->relationName, $value)) {
                $with[$key] = $this->relationName.'.'.$value;
            } elseif (! str_starts_with($this->relationName, $key)) {
                unset($with[$key]);
                $with[$this->relationName.'.'.$key] = $value;
            }
        }

        return $with;
    }
}
