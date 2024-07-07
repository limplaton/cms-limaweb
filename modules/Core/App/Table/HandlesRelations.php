<?php
 

namespace Modules\Core\App\Table;

/** @mixin \Modules\Core\App\Table\Table */
trait HandlesRelations
{
    protected ?array $countRelations = null;

    /**
     * Get all tables relation to be eager loaded with specific selected fields
     */
    protected function getWithRelationships(): array
    {
        $columns = $this->getUserColumns()
            ->reject(fn ($column) => $column instanceof RelationshipCountColumn)
            ->filter->shouldQuery();

        $selectWith = [];

        $with = array_merge($columns->reduce(
            fn (array $carry, Column $column) => $carry + $column->with, []
        ), $this->with);

        $columns->whereInstanceOf(RelationshipColumn::class)
            ->each(function (RelationshipColumn $column) use (&$with, &$selectWith) {
                $selectRelationFields = $this->getFieldsToSelectForRelationship($column);

                // Check if the relation is already queried
                // E.q. example usage on deals table
                // Column stage name and column stage win_probability from the same relation
                // In this case, Laravel will only perform query on the last selected relation
                // and the previous relation will be lost
                // for this reason we need to merge both relation in one select with the
                // selected fields from both relation

                $relationExists = collect($with)->contains(
                    fn ($callback, $relationName) => $column->relationName === $relationName
                );

                if (! $relationExists) {
                    $with[$column->relationName] = function ($query) use ($selectRelationFields) {
                        $query->select($selectRelationFields);
                    };

                    $selectWith[$column->relationName] = $selectRelationFields;
                } else {
                    // Merge the selected relation fields
                    $newSelect = array_unique(
                        array_merge($selectWith[$column->relationName], $selectRelationFields)
                    );

                    // Update the existent relation with the new merged select
                    $with[$column->relationName] = function ($query) use ($newSelect) {
                        $query->select($newSelect);
                    };
                }
            });

        return $with;
    }

    /**
     * Get the relations that should be counted
     */
    protected function getCountedRelationships(): array
    {
        if (! is_null($this->countRelations)) {
            return $this->countRelations;
        }

        /** @var \Illuminate\Support\Collection */
        $columns = $this->getUserColumns();

        return $this->countRelations = $columns->whereInstanceOf(RelationshipCountColumn::class)
            ->reject(function ($column) {
                /**
                 * Check if the table is sorting by current countable column
                 * If at the moment of the request the column is hidden and the user set
                 * e.q. default sorting, an error will be triggered because the relationship
                 * count is not queried.
                 *
                 * In such case, we must query the column when is hidden to perform sorting
                 */
                return $column->isHidden() && ! $this->isSortingByColumn($column);
            })
            ->map(fn ($column) => "{$column->relationshipName} as {$column->attribute}")
            ->union($columns->reject->isHidden()->pluck('withCount')->reduce(function ($carry, array $relations) {
                return array_merge($carry, $relations);
            }, []))
            ->push(...$this->withCount)
            ->filter()
            ->all();
    }

    /**
     * Get fields that should be selected with the eager loaded relation e.q. with(['company:id,name'])
     *
     * Adds the foreign key name to the select as is needed for Laravel to merge the data from the with query
     */
    protected function getFieldsToSelectForRelationship(RelationshipColumn $column): array
    {
        $select = [$this->getSelectableField($column)];
        $relationship = $this->model->{$column->relationName}();

        if ($column instanceof BelongsToColumn || $column instanceof MorphToManyColumn) {
            $select[] = $relationship->getRelated()->getQualifiedKeyName();
        } elseif ($column instanceof MorphManyColumn) {
            $select[] = $relationship->getModel()->getQualifiedKeyName();
            $select[] = $relationship->getQualifiedForeignKeyName();
        } elseif ($column instanceof HasManyColumn || $column instanceof HasOneColumn) {
            $select[] = $relationship->getQualifiedForeignKeyName();
        } elseif ($column instanceof BelongsToManyColumn) {
            $select[] = $relationship->getModel()->getQualifiedKeyName();
        }

        return collect($select)->merge(
            $this->qualifyColumn($column->select, $column->relationName)
        )->unique()->values()->all();
    }
}
