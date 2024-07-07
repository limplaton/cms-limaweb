<?php
 

namespace Modules\Core\App\Resource\Query;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Contracts\Resources\AcceptsCustomFields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Resource\Resource;

class EloquentQueryBuilder extends Builder
{
    /**
     * Set the relationships that should be eager loaded.
     *
     * The method ensures when regularly using $query->with('resourceRelationWithCustomFields')
     * is loading the resource optionable custom fields to avoid eager loading violation.
     *
     * @param  string|array  $relations
     * @param  string|\Closure|null  $callback
     * @return $this
     */
    public function with($relations, $callback = null)
    {
        $instance = parent::with(...func_get_args());

        foreach (array_keys($this->eagerLoad) as $relation) {
            // We do not deep lost optionable custom fields.
            if (str_contains($relation, '.')) {
                continue;
            }

            $relatedModel = $this->getModel()->{$relation}()->getModel();
            $relatedResource = Innoclapps::resourceByModel($relatedModel);

            if ($relatedResource instanceof AcceptsCustomFields) {
                $this->withResourceRelationCustomFields($relation, $relatedResource);
            }
        }

        return $instance;
    }

    /**
     * Eager load resource relation custom fields.
     */
    protected function withResourceRelationCustomFields(string $relation, Resource $resource): void
    {
        $eagerLoad = $resource
            ->customFields()
            ->optionable()
            ->pluck('relationName')
            ->map(fn (string $customFieldRelation) => $relation.'.'.$customFieldRelation)
            ->all();

        // Ensure to add/override the original relation we are adding the custom fields
        // relation for eager loading, as select statement don't work if it's not added.
        // Model::with(['someRelationWithCustomFields' => function ($query) {
        //     // This won't work
        //     $query->select(['id', 'some_field']);
        // }])->get();
        $eagerLoad[$relation] = $this->eagerLoad[$relation];

        parent::with($eagerLoad);
    }
}
