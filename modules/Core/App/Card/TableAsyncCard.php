<?php
 

namespace Modules\Core\App\Card;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Criteria\RequestCriteria;
use Modules\Core\App\Settings\Utilities\Arr;
use Modules\Core\App\Support\GateHelper;

abstract class TableAsyncCard extends Card
{
    use FloatsResource;

    /**
     * Default sort field.
     */
    protected Expression|string|null $sortBy = 'id';

    /**
     * Default sort direction.
     */
    protected string $sortDirection = 'asc';

    /**
     * Default per page.
     */
    protected int $perPage = 15;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected ?Builder $query = null;

    /**
     * Indicates whether the table is searchable.
     */
    protected bool $searchable = true;

    /**
     * Provide the query that will be used to retrieve the items.
     */
    abstract public function query(Request $request): Builder;

    /**
     * Get the card value.
     */
    public function value(Request $request): mixed
    {
        return JsonResource::collection(
            $this->transformResult($this->performQuery($request), $request)
        )
            ->toResponse($request)
            ->getData();
    }

    /**
     * Provide the table fields.
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Get the query instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getQuery(Request $request)
    {
        return $this->query ??= $this->query($request)->criteria($this->getRequestCriteria());
    }

    /**
     * Get the request criteria.
     */
    protected function getRequestCriteria(): RequestCriteria
    {
        return (new RequestCriteria)->setSearchFields($this->getSearchableColumns());
    }

    /**
     * Get the searchable columns.
     */
    protected function getSearchableColumns(): array
    {
        return Arr::pluck($this->fields(), 'key');
    }

    /**
     * Retrieve the items from storage.
     */
    protected function performQuery(Request $request): LengthAwarePaginator
    {
        $query = $this->getQuery($request)->when($this->getSortColumn(), function ($query, $orderByColumn) {
            $query->orderBy($orderByColumn, $this->sortDirection);
        });

        return tap($query->paginate(
            $this->getPerPage($request),
            $this->selectColumns($request)
        ), function ($data) {
            $this->query = null;
        });
    }

    /**
     * Get the per page param.
     */
    protected function getPerPage(Request $request)
    {
        return $request->integer('per_page', $this->perPage);
    }

    /**
     * Get the sort column.
     */
    protected function getSortColumn(): Expression|string|null
    {
        return $this->sortBy;
    }

    /**
     * Get the columns that should be selected in the query.
     */
    protected function selectColumns(Request $request): array
    {
        return collect($this->fields())->reject(function ($field) {
            return isset($field['select']) && $field['select'] === false;
        })->pluck('key')->push(
            $this->getQuery($request)->getModel()->getKeyName()
        )->all();
    }

    /**
     * Parse the query result.
     */
    protected function transformResult(LengthAwarePaginator $result, Request $request): LengthAwarePaginator
    {
        $result->getCollection()->transform(fn ($model) => $this->mapRow($model, $request));

        return $result;
    }

    /**
     * Map the given model into a row.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function mapRow($model, Request $request)
    {
        $result = collect($this->fields())
            ->merge(array_map(fn ($column) => ['key' => $column], $this->selectColumns($request)))
            ->unique('key')
            ->mapWithKeys(function (array $field) use ($model) {
                $value = isset($field['format']) ? $field['format']($model) : data_get($model, $field['key']);

                return [$field['key'] => $value];
            })->all();

        if ($model instanceof Presentable) {
            $result['path'] = $model->path();
        }

        $result['authorizations'] = GateHelper::authorizations($model);

        return $result;
    }

    /**
     * Define the card component used on front end.
     */
    public function component(): string
    {
        return 'card-async-table';
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'searchable' => $this->searchable,
            'fields' => $this->fields(),
            'floatingResource' => $this->floatingResource,
        ]);
    }
}
