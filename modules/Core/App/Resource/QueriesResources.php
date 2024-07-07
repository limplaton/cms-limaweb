<?php
 

namespace Modules\Core\App\Resource;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Concerns\UserOrderable;
use Modules\Core\App\Contracts\Fields\Customfieldable;
use Modules\Core\App\Criteria\ExportRequestCriteria;
use Modules\Core\App\Criteria\RequestCriteria;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\BelongsTo;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Fields\HasMany;
use Modules\Core\App\Fields\MorphMany;
use Modules\Core\App\Fields\MorphToMany;
use Modules\Core\App\Fields\RelationshipCount;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\PinnedTimelineSubject;

/**
 * @mixin \Modules\Core\App\Resource\Resource
 */
trait QueriesResources
{
    /**
     * Get a new query builder for the resource's model table.
     */
    public function newQuery(): Builder
    {
        return $this->newModel()->newQuery();
    }

    /**
     * Get a new query builder for the resource's model table that includes trashed records.
     */
    public function newQueryWithTrashed(): Builder
    {
        return $this->newModel()->withTrashed();
    }

    /**
     * Prepare display query.
     */
    public function displayQuery(): Builder
    {
        $query = $this->applyWith($this->newQuery(), $this->resolveFields());

        if (count($this->associateableRelations()) > 0) {
            $query->withCountAssociations();
        }

        return $query;
    }

    /**
     * Prepare index query.
     */
    public function indexQuery(ResourceRequest $request): Builder
    {
        $query = $this->newQueryWithAuthorizedRecordsCriteria();

        if ($request->missing(RequestCriteria::ORDER_KEY)) {
            $this->applyDefaultOrder($query);
        }

        $query->criteria([
            $this->getFiltersCriteria($request, 'filters'),
            $this->getRequestCriteria($request),
        ]);

        return $this->applyWith($query, $this->fieldsForIndexQuery());
    }

    /**
     * Prepare global search query.
     */
    public function globalSearchQuery(ResourceRequest $request): Builder
    {
        $query = $this->newQueryWithAuthorizedRecordsCriteria();

        $query->criteria($this->getRequestCriteria($request, $this->globalSearchColumns()));

        if ($request->missing(RequestCriteria::ORDER_KEY)) {
            $this->applyDefaultOrder($query);
        }

        return $this->applyDefaultOrder($query);
    }

    /**
     * Prepare search query.
     */
    public function searchQuery(ResourceRequest $request): Builder
    {
        $query = $this->newQueryWithAuthorizedRecordsCriteria();

        $query->criteria($this->getRequestCriteria($request));

        if ($request->missing(RequestCriteria::ORDER_KEY)) {
            $this->applyDefaultOrder($query);
        }

        return $this->applyWith($query, $this->resolveFields());
    }

    /**
     * Create new trashed query instance.
     */
    public function newTrashedQuery(): Builder
    {
        return $this->newQuery()->onlyTrashed();
    }

    /**
     * Prepare trashed index query.
     */
    public function trashedIndexQuery(ResourceRequest $request): Builder
    {
        return $this->indexQuery($request)->onlyTrashed();
    }

    /**
     * Prepare trashed display query.
     */
    public function trashedDisplayQuery(): Builder
    {
        return $this->displayQuery()->onlyTrashed();
    }

    /**
     * Prepare search query for trashed records.
     */
    public function trashedSearchQuery(ResourceRequest $request): Builder
    {
        return $this->searchQuery($request)->onlyTrashed();
    }

    /**
     * Prepare an export query.
     */
    public function exportQuery(ResourceRequest $request, ?FieldsCollection $fields = null): Builder
    {
        $query = $this->newQueryWithAuthorizedRecordsCriteria();

        $query->criteria(new ExportRequestCriteria(
            $request->input('period'),
            $request->input('date_range_field')
        ));

        if ($request->filters) {
            $query->criteria($this->getFiltersCriteria($request, 'filters'));
        }

        if ($request->missing(RequestCriteria::ORDER_KEY)) {
            $this->applyDefaultOrder($query);
        }

        return $this->applyWith($query, $fields ?? $this->fieldsForExport());
    }

    /**
     * Prepare table query.
     */
    public function tableQuery(ResourceRequest $request): Builder
    {
        return $this->newQueryWithAuthorizedRecordsCriteria();
    }

    /**
     * Create new query with the authorized records criteria.
     */
    public function newQueryWithAuthorizedRecordsCriteria(): Builder
    {
        $query = $this->newQuery();

        if ($criteria = $this->viewAuthorizedRecordsCriteria()) {
            $query->criteria($criteria);
        }

        return $query;
    }

    /**
     * Create the query when the resource is associated and the data is intended for the timeline.
     */
    public function timelineQuery(Model $subject, ResourceRequest $request): Builder
    {
        $relation = Innoclapps::resourceByModel($subject)->associateableName();

        $query = $this->newQuery()
            ->select($this->newModel()->prefixColumns())
            ->criteria($this->getRequestCriteria($request))
            ->whereHas($relation, function ($query) use ($subject) {
                return $query->where($subject->getKeyName(), $subject->getKey());
            })
            ->with('pinnedTimelineSubjects')
            ->withTimelinePins($subject)
            ->orderBy((new PinnedTimelineSubject)->getQualifiedCreatedAtColumn(), 'desc');

        if ($query->getModel()->usesTimestamps()) {
            $query->orderBy($query->getModel()->getQualifiedCreatedAtColumn(), 'desc');
        }

        return $this->applyWith($query)->withCountAssociations();
    }

    /**
     * Apply the default order from the resource to the given query.
     */
    public function applyDefaultOrder(Builder $query): Builder
    {
        if (in_array(UserOrderable::class, class_uses_recursive(static::$model))) {
            return $query->userOrdered();
        } else {
            return $query->orderBy(static::$orderBy, static::$orderByDir);
        }
    }

    /**
     * Add "with" relations to the given query.
     */
    public function with(Builder $query): Builder
    {
        return $query->withCommon();
    }

    /**
     * Add "with count" relations to the given query.
     */
    public function withCount(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Add "with" relations to the given query from the given fields.
     */
    public function withViaFields(Builder $query, $fields): Builder
    {
        $fields = $fields->withoutZapierExcluded();

        $relations = $fields->pluck('with')->flatten()
            ->merge($fields->whereInstanceOf(BelongsTo::class)->pluck('belongsToRelation'))
            ->merge($fields->whereInstanceOf(HasMany::class)->pluck('hasManyRelationship'))
            ->merge($fields->whereInstanceOf(MorphMany::class)->pluck('morphManyRelationship'))
            ->merge($fields->whereInstanceOf(MorphToMany::class)->pluck('morphToManyRelationship'))
            ->merge($fields->filterCustomFields()->filter(function (Field&Customfieldable $field) {
                return $field->isOptionable();
            })->pluck('customField.relationName'))
            ->filter()
            ->unique();

        return $query->with($relations->all());
    }

    /**
     * Add "with count" relations to the given query from the given fields.
     */
    public function withCountViaFields(Builder $query, $fields)
    {
        return $query->withCount(
            $fields->whereInstanceOf(RelationshipCount::class)
                ->pluck('countRelation')
                ->filter()
                ->unique()
                ->all()
        );
    }

    /**
     * Apply the resource eager loaded relations and counts for the given query and/or fields.
     */
    protected function applyWith(Builder $query, $fields = null): Builder
    {
        if ($fields) {
            $this->withViaFields($query, $fields);
            $this->withCountViaFields($query, $fields);
        }

        $this->with($query);
        $this->withCount($query);

        return $query;
    }

    /**
     * Get the fields when creating index query
     */
    protected function fieldsForIndexQuery(): FieldsCollection
    {
        return $this->resolveFields()->reject(fn (Field $field) => $field->isExcludedFromIndexQuery);
    }
}
