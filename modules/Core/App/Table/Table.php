<?php
 

namespace Modules\Core\App\Table;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Core\App\Actions\ForceDeleteAction;
use Modules\Core\App\Actions\ResolvesActions;
use Modules\Core\App\Actions\RestoreAction;
use Modules\Core\App\Criteria\FilterRulesCriteria;
use Modules\Core\App\Criteria\TableRequestCriteria;
use Modules\Core\App\Filters\ResolvesFilters;
use Modules\Core\App\Http\Requests\ResourceRequest;

class Table
{
    use HandlesRelations,
        ParsesResponse,
        ResolvesActions,
        ResolvesFilters;

    /**
     * Additional database columns to select for the query.
     */
    protected array $select = [];

    /**
     * Additional attributes to be appended with the response.
     */
    protected array $appends = [];

    /**
     * Additional relations to eager load for the query.
     */
    protected array $with = [];

    /**
     * Table order.
     */
    public array $order = [];

    /**
     * Additional countable relations to eager load for the query.
     */
    protected array $withCount = [];

    /**
     * Custom table filters.
     */
    protected Collection|array $filters = [];

    /**
     * Custom table actions.
     */
    protected Collection|array $actions = [];

    /**
     * Table identifier.
     */
    protected string $identifier;

    /**
     * Additional request query string for the table request.
     */
    public array $requestQueryString = [];

    /**
     * Table default per page value.
     */
    public int $perPage = 25;

    /**
     * All time total count.
     */
    public ?int $preTotal = null;

    /**
     * Whether the table columns can be customized.
     *
     * You must ensure all columns has unique ID's before setting this properoty to true.
     */
    public bool $customizeable = false;

    /**
     * Whether the table sorting options can be changed.
     * Only works if $customizeable is set to true.
     */
    public bool $allowDefaultSortChange = true;

    /**
     * Whether the table has actions column.
     */
    public bool $withActionsColumn = false;

    /**
     * Table max height.
     *
     * @var int|null
     */
    public $maxHeight = null;

    /**
     * Additional meta to include in the response.
     */
    public array $meta = [];

    /**
     * The query model instance.
     *
     * @var \Modules\Core\App\Models\Model
     */
    protected $model = null;

    /**
     * Table settings.
     */
    protected TableSettings $settings;

    /**
     * Columns collection.
     */
    protected Collection $columns;

    /**
     * @var null|callable
     */
    protected $provideRowClassUsing = null;

    /**
     * @var null|callable|string
     */
    protected $rowBorderVariant = null;

    /**
     * Initialize new Table instance.
     */
    public function __construct(protected Builder $query, protected ResourceRequest $request, ?string $identifier = null)
    {
        $this->model = $query->getModel();

        $this->setIdentifier($identifier ?: Str::kebab(class_basename(static::class)))
            ->setColumns($this->columns())
            ->setSettings(new TableSettings($this, $this->request->user()))
            ->boot();
    }

    /**
     * Custom boot method.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Provide the table columns.
     */
    public function columns(): array
    {
        return [];
    }

    /**
     * Set the table available columns.
     */
    public function setColumns(array|Collection $columns): static
    {
        $this->columns = is_array($columns) ? new Collection($columns) : $columns;

        if ($this->withActionsColumn === true) {
            // Check if we need to add the action
            if (! $this->columns->whereInstanceOf(ActionColumn::class)->first()) {
                $this->addColumn(new ActionColumn);
            }
        }

        return $this;
    }

    /**
     * Add new column to the table.
     */
    public function addColumn(Column $column): static
    {
        $this->columns->push($column);

        return $this;
    }

    /**
     * Creates the table data and return the data
     */
    public function make(): LengthAwarePaginator
    {
        return $this->parseResponse(
            $this->getQuery()->paginate($this->getPerPage())
        );
    }

    /**
     * Get the query intended for the table records.
     */
    public function getQuery(): Builder
    {
        // If you're combining withCount with a select statement,
        // ensure that you call withCount after the select method
        return $this->query->select($this->getColumnsToSelect())
            ->with($this->getWithRelationships())
            ->withCount($this->getCountedRelationships())
            ->criteria([$this->newRequestCriteria(), $this->newFilterRulesCriteria()]);
    }

    /**
     * Set the total before any where (except authorizations related) queries are performed.
     */
    public function setPreTotal(int $total): static
    {
        $this->preTotal = $total;

        return $this;
    }

    /**
     * Provide row class using a custom callback.
     */
    public function provideRowClassUsing(callable $callback): static
    {
        $this->provideRowClassUsing = $callback;

        return $this;
    }

    /**
     * Provide row border variant.
     */
    public function rowBorderVariant(callable|string $callback): static
    {
        $this->rowBorderVariant = $callback;

        return $this;
    }

    /**
     * Get the table request instance.
     */
    public function getRequest(): ResourceRequest
    {
        return $this->request;
    }

    /**
     * Get the server for the table AJAX request params.
     */
    public function getRequestQueryString(): array
    {
        return $this->requestQueryString;
    }

    /**
     * Set table default order by.
     */
    public function orderBy(string $attribute, string $dir = 'asc'): static
    {
        $this->order[] = ['attribute' => $attribute, 'direction' => $dir];

        return $this;
    }

    /**
     * Clear the order by attributes.
     */
    public function clearOrderBy(): static
    {
        $this->order = [];

        return $this;
    }

    /**
     * Add additional relations to eager load.
     */
    public function with(string|array $relations): static
    {
        $this->with = array_merge($this->with, (array) $relations);

        return $this;
    }

    /**
     * Add additional countable relations to eager load
     */
    public function withCount(string|array $relations): static
    {
        $this->withCount = array_merge($this->withCount, (array) $relations);

        return $this;
    }

    /**
     * Get the table available table filters
     *
     * Checks for custom configured filters
     */
    public function filters(ResourceRequest $request): array|Collection
    {
        return $this->filters;
    }

    /**
     * Set table available filters
     */
    public function setFilters(array|Collection $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Available table actions
     */
    public function actions(ResourceRequest $request): array|Collection
    {
        return $this->actions;
    }

    /**
     * Set the table available actions
     */
    public function setActions(array|Collection $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Indicates whether the table is with actions column.
     */
    public function withActionsColumn(): static
    {
        $this->withActionsColumn = true;
        $this->addColumn(new ActionColumn);

        return $this;
    }

    /**
     * Remove the action column from the table.
     */
    public function withoutActionsColumn(): static
    {
        $this->withActionsColumn = false;

        $this->setColumns($this->columns->reject(fn (Column $column) => $column instanceof ActionColumn)->values());

        return $this;
    }

    /**
     * Get defined column by given attribute.
     */
    public function getColumn(string $attribute): ?Column
    {
        return $this->columns->firstWhere('attribute', $attribute);
    }

    /**
     * Get all of the table available columns.
     */
    public function getColumns(): Collection
    {
        return $this->columns;
    }

    /**
     * Check if the table is sorted by specific column
     */
    public function isSortingByColumn(Column $column): bool
    {
        $sortingBy = $this->request->get('order', []);
        $sortedByFields = data_get($sortingBy, '*.attribute');

        return in_array($column->attribute, $sortedByFields);
    }

    /**
     * Get the table settings for the current request.
     */
    public function settings(): TableSettings
    {
        return $this->settings;
    }

    /**
     * Set the table settings.
     */
    public function setSettings(TableSettings $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get the table identifier.
     */
    public function identifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set table identifier.
     */
    public function setIdentifier(string $key): static
    {
        $this->identifier = $key;

        return $this;
    }

    /**
     * Mark the table as customizeable.
     */
    public function customizeable(bool $value = true): static
    {
        $this->customizeable = $value;

        return $this;
    }

    /**
     * Add additional database columns to select.
     */
    public function select(string|array $columns): static
    {
        $this->select = array_merge($this->select, (array) $columns);

        return $this;
    }

    /**
     * Get the table per page limit.
     */
    public function getPerPage(): int
    {
        return $this->request->integer('per_page', $this->perPage);
    }

    /**
     * Get the actions when the table is intended to be displayed on the trashed view.
     *
     * NOTE: No authorization is performed on these action, all actions will be visible to the user
     */
    public function trashedViewActions(): array
    {
        return [new RestoreAction, new ForceDeleteAction];
    }

    /**
     * Add attributes that should be appended in the response.
     */
    public function appends(string|array $attributes): static
    {
        $this->appends = array_merge($this->appends, (array) $attributes);

        return $this;
    }

    /**
     * Get column to select for the table query.
     *
     * Will return that columns only that are needed for the table,
     * For example of the user made some columns not visible they won't be queried.
     */
    protected function getColumnsToSelect(): array
    {
        $columns = $this->getUserColumns()->filter->shouldQuery();

        $select = [];

        foreach ($columns as $column) {
            if (! $column->isRelation()) {
                if ($field = $this->getSelectableField($column)) {
                    $select[] = $field;
                }

                $select = array_merge($select, $this->qualifyColumn($column->select));

            } elseif ($column instanceof BelongsToColumn) {
                // Select the foreign key name for the BelongsToColumn
                // If not selected, the relation won't be queried properly
                $select[] = $this->model->{$column->relationName}()->getQualifiedForeignKeyName();
            }
        }

        return array_unique(array_merge(
            $this->qualifyColumn($this->select),
            [$this->model->getQualifiedKeyName().' as '.$this->model->getKeyName()],
            $select
        ), SORT_REGULAR);
    }

    /**
     * Prepare the searchable columns for the model from the table defined columns.
     */
    protected function prepareSearchableColumns(): array
    {
        return $this->getSearchableColumns()->mapWithKeys(function (Column|RelationshipColumn $column) {
            if ($column->isRelation()) {
                $searchableField = $column->relationName.'.'.$column->relationField;
            } else {
                $searchableField = $column->attribute;
            }

            return [$searchableField => 'like'];
        })->all();
    }

    /**
     * Filter the searchable columns.
     */
    protected function getSearchableColumns(): Collection
    {
        return $this->getUserColumns()->filter(function (Column $column) {
            // We will check if the column is date column, as date columns are not searchable
            // as there won't be accurate results because the database dates are stored in UTC timezone
            // In this case, the filters must be used
            // Additionally we will check if is countable column and the column counts
            if ($column instanceof DateTimeColumn ||
                $column instanceof DateColumn ||
                $column instanceof ActionColumn ||
                $column instanceof RelationshipCountColumn) {
                return false;
            }

            // Relation columns with no custom query are searchable
            if ($column->isRelation()) {
                return empty($column->queryAs);
            }

            // Regular database, and also is not queried
            // with DB::raw, when querying with DB::raw, you must implement
            // custom searching criteria
            return empty($column->queryAs);
        });
    }

    /**
     * Create new TableRequestCriteria criteria instance.
     */
    protected function newRequestCriteria(): TableRequestCriteria
    {
        return (new TableRequestCriteria($this))->setSearchFields($this->prepareSearchableColumns());
    }

    /**
     * Create new FilterRulesCriteria criteria instance.
     */
    protected function newFilterRulesCriteria(): FilterRulesCriteria
    {
        return new FilterRulesCriteria(
            $this->request->get('rules'),
            $this->resolveFilters($this->request),
            $this->request
        );
    }

    /**
     * Get field by column that should be included in the table select query.
     */
    protected function getSelectableField(Column|RelationshipColumn $column): mixed
    {
        if ($column instanceof ActionColumn || $column instanceof RelationshipCountColumn) {
            return null;
        }

        if (! empty($column->queryAs)) {
            return $column->queryAs;
        } elseif ($column instanceof RelationshipColumn) {
            return $this->qualifyColumn($column->relationField, $column->relationName);
        }

        return $this->qualifyColumn($column->attribute);
    }

    /**
     * Qualify the given column.
     */
    protected function qualifyColumn(string|array $column, ?string $forRelationship = null): array|string
    {
        if (is_array($column)) {
            return array_map(fn ($column) => $this->qualifyColumn($column, $forRelationship), $column);
        }

        if ($forRelationship) {
            return $this->model->{$forRelationship}()->qualifyColumn($column);
        }

        return $this->model->qualifyColumn($column);
    }

    /**
     * Get the columns for the table intended to be shown to the logged in user.
     */
    protected function getUserColumns(): Collection
    {
        return $this->settings()->getColumns();
    }
}
