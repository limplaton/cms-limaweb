<?php
 

namespace Modules\Core\App\Table;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use JsonSerializable;
use Modules\Core\App\Contracts\Metable;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Http\Resources\FilterResource;
use Modules\Core\App\Models\Filter;
use Modules\Core\App\Table\Exceptions\OrderByNonExistingColumnException;
use Modules\Users\App\Models\User;

class TableSettings implements Arrayable, JsonSerializable
{
    const MIN_POLLING_INTERVAL = 10;

    const META_PREFIX_KEY = 'table-settings';

    /**
     * Columns cache.
     */
    protected ?Collection $columns = null;

    /**
     * Create new TableSettings instance.
     */
    public function __construct(protected Table $table, protected User&Metable $user)
    {
    }

    /**
     * Get the table available actions.
     *
     * The function removes also the actions that are hidden on INDEX
     */
    public function actions(): Collection
    {
        return $this->table->resolveActions($this->table->getRequest())
            ->reject(fn ($action) => $action->hideOnIndex === true)
            ->values();
    }

    /**
     * Get the available table saved filters.
     */
    public function savedFilters(): Collection
    {
        return Filter::forUser($this->user->id, $this->table->identifier())->get();
    }

    /**
     * Get the table max height.
     */
    public function maxHeight(): float|int|string|null
    {
        return $this->getCustomizedSettings('maxHeight') ?? $this->table->maxHeight;
    }

    /**
     * Get the table polling interval.
     */
    public function pollingInterval(): ?int
    {
        $interval = $this->getCustomizedSettings('pollingInterval');

        if (! is_null($interval)) {
            $interval = $interval < static::MIN_POLLING_INTERVAL ? static::MIN_POLLING_INTERVAL : $interval;
        }

        return $interval;
    }

    /**
     * Check whether the table is condensed.
     */
    public function isCondensed(): bool
    {
        return $this->getCustomizedSettings('condensed') ?? false;
    }

    /**
     * Check whether the table is bordered.
     */
    public function isBordered(): bool
    {
        return $this->getCustomizedSettings('bordered') ?? false;
    }

    /**
     * Get the table per page.
     */
    public function perPage(): int
    {
        return $this->getCustomizedSettings('perPage') ?? $this->table->perPage;
    }

    /**
     * Saves customized table data.
     */
    public function update(?array $data): static
    {
        if (! empty($data)) {
            $this->guardColumns($data);

            $data['columns'] = $this->mergeColumnsWithCurrent($data['columns']);
        }

        $this->user->setMeta($this->getMetaName(), $data);
        $this->user = User::find($this->user->id);

        return $this;
    }

    /**
     * Merge the given customized columns with the current customized columns in storage.
     */
    protected function mergeColumnsWithCurrent(array $updatedColumns): array
    {
        $columns = $this->getCustomizedColumns()->keyBy('attribute');

        foreach ($updatedColumns as $config) {
            $columns[$config['attribute']] = $config;
        }

        return $columns->values()->all();
    }

    /**
     * Get the user columns meta name.
     */
    protected function getMetaName(): string
    {
        return static::META_PREFIX_KEY.'-'.$this->table->identifier();
    }

    /**
     * Get table order, checks for custom ordering too.
     */
    public function getOrder(): array
    {
        $customizedOrder = $this->getCustomizedOrderBy();

        if (count($customizedOrder) === 0) {
            return $this->table->order;
        }

        return collect($customizedOrder)->reject(function ($data) {
            // Check and unset the custom ordered field in case no longer exists as available columns
            // For example it can happen a database change and this column is no longer available,
            // for this reason we must not sort by this column because it may be removed from database
            return is_null($this->table->getColumn($data['attribute']));
        })->values()->all();
    }

    /**
     * Validate the order of the table columns.
     *
     * @throws \Modules\Core\App\Table\Exceptions\OrderByNonExistingColumnException
     */
    protected function validateOrder(array $order): array
    {
        foreach ($order as $data) {
            throw_if(
                is_null($this->table->getColumn($data['attribute'])),
                new OrderByNonExistingColumnException($data['attribute'])
            );
        }

        return $order;
    }

    /**
     * Get the actual table columns that should be displayed to the user.
     */
    public function getColumns(): Collection
    {
        // We will retrieve and configure all columns for the table and store them in property cache
        // after that we will add any customized attributes to the column field (if set)
        // so in case are used for inline edit, validation and other data is set correctly.
        return $this->columns ??= $this->retrieveAndConfigureColumns()
            ->filter()
            ->each(function (Column $column) {
                if ($column->field) {
                    Fields::applyCustomizedAttributes(
                        $column->field,
                        $this->table->identifier(),
                        Fields::UPDATE_VIEW
                    );
                }
            })
            ->values();
    }

    /**
     * Get the available columns from the table and authorize.
     */
    protected function getAuthorizedColumns(): Collection
    {
        return $this->table->getColumns()->filter(fn (Column $column) => $column->authorizedToSee())->values();
    }

    /**
     * Retrieve and configure the table columns.
     */
    protected function retrieveAndConfigureColumns(): Collection
    {
        $columns = $this->getCustomizedColumns();
        $availableColumns = $this->getAuthorizedColumns();

        // Merge the order and the visibility and all columns so we can filter them later
        return $availableColumns
            ->each(function (Column $column, int $index) use ($columns) {
                if ($column instanceof ActionColumn) {
                    $column->order(1000)->hidden(false);
                } else {
                    $data = $columns->firstWhere('attribute', $column->attribute);

                    $column
                        ->order((int) ($data['order'] ?? $index + 1))
                        ->hidden($data['hidden'] ?? $column->hidden ?? false)
                        ->width($data['width'] ?? $column->width)
                        ->wrap($data['wrap'] ?? $column->wrap);
                }
            })

            ->sortBy('order')
            ->values()
            ->when(true, function (Collection $columns) {
                // Ensure that the primary column is always first.
                $primaryIndex = $columns->search(fn (Column $column) => $column->isPrimary());

                if ($primaryIndex !== -1 && $primaryIndex !== 0) {
                    $primaryColumn = $columns->get($primaryIndex);
                    $columns->forget($primaryIndex)->prepend($primaryColumn);
                }

                return $columns;
            });
    }

    /**
     * Get user customized table data that is stored in database/meta.
     */
    public function getCustomizedSettings(?string $key = null): mixed
    {
        $settings = $this->user->getMeta($this->getMetaName());

        return $key ? ($settings[$key] ?? null) : $settings;
    }

    /**
     * Get table customized user order by.
     */
    public function getCustomizedOrderBy(): array
    {
        return $this->getCustomizedSettings('order') ?? [];
    }

    /**
     * Get table customized user columns.
     */
    public function getCustomizedColumns(): Collection
    {
        return new Collection($this->getCustomizedSettings('columns') ?? []);
    }

    /**
     * Guard the primary and not sortable columns.
     *
     * Protects the primary fields visibility when direct API request is performed.
     */
    protected function guardColumns(array &$payload): void
    {
        $this->guardPrimaryColumns($payload);
        $this->guardNotSortableColumns($payload);
    }

    /**
     * Guards the primary fields from mole changes via API.
     */
    protected function guardPrimaryColumns(array &$payload): void
    {
        // Protected the primary fields hidden option
        // when direct API request
        // e.q. the field attribute hidden is set to false when it must be visible
        // because the field is marked as primary field
        foreach ($payload['columns'] as $key => $column) {
            $column = $this->table->getColumn($column['attribute']);

            // Reset with the default attributes for additional protection
            if ($column?->isPrimary()) {
                $payload['columns'][$key]['hidden'] = $column->isHidden();
            }
        }
    }

    /**
     * Guards the not sortable columns from mole changes via API.
     */
    protected function guardNotSortableColumns(array &$payload): void
    {
        // Protected the not sortable columns
        // E.q. if column is marked to be not sortable
        // The user is not allowed to change to sortable
        foreach ($payload['order'] as $key => $sort) {
            $column = $this->table->getColumn($sort['attribute']);

            // Reset with the default attributes for additional protection
            if (! $column->isSortable()) {
                unset($payload['order'][$key]);
            }
        }
    }

    /**
     * Get the saved filters resource.
     */
    public function filtersResource(): AnonymousResourceCollection
    {
        return FilterResource::collection($this->savedFilters());
    }

    /**
     * toArray
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->table->identifier(),
            'allowDefaultSortChange' => $this->table->allowDefaultSortChange,
            'requestQueryString' => $this->table->getRequestQueryString(),
            'customizeable' => $this->table->customizeable,
            'rules' => $this->table->resolveFilters($this->table->getRequest()),
            'perPage' => $this->perPage(),
            'maxHeight' => $this->maxHeight(),
            'pollingInterval' => $this->pollingInterval(),
            'minimumPollingInterval' => static::MIN_POLLING_INTERVAL,
            'condensed' => $this->isCondensed(),
            'bordered' => $this->isBordered(),
            'filters' => $this->filtersResource(),
            'columns' => $this->getColumns(),
            'order' => $this->validateOrder($this->getOrder()),
            'actions' => $this->actions(),
        ];
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
