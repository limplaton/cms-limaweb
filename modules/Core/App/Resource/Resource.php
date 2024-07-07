<?php
 

namespace Modules\Core\App\Resource;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonSerializable;
use Modules\Core\App\Actions\ResolvesActions;
use Modules\Core\App\Contracts\Resources\AcceptsUniqueCustomFields;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Criteria\FilterRulesCriteria;
use Modules\Core\App\Criteria\RequestCriteria;
use Modules\Core\App\Facades\Cards;
use Modules\Core\App\Facades\Fields;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Facades\Menu;
use Modules\Core\App\Facades\SettingsMenu;
use Modules\Core\App\Fields\CustomFieldCollection;
use Modules\Core\App\Fields\CustomFieldFactory;
use Modules\Core\App\Fields\CustomFieldFileCache;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Filters\ResolvesFilters;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\CustomField;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Events\ResourceRecordCreated;
use Modules\Core\App\Resource\Events\ResourceRecordDeleted;
use Modules\Core\App\Resource\Events\ResourceRecordUpdated;
use Modules\Core\App\Resource\Import\Import;
use Modules\Core\App\Resource\Import\ImportSample;
use Modules\Users\App\Models\User;

abstract class Resource implements JsonSerializable
{
    use HasResourceEvents,
        QueriesResources,
        ResolvesActions,
        ResolvesFields,
        ResolvesTables;
    use ResolvesFilters {
        ResolvesFilters::resolveFilters as resolveBaseFilters;
    }

    /**
     * The column the records should be default ordered by when retrieving.
     */
    public static string $orderBy = 'id';

    /**
     * The direction the records should be default ordered by when retrieving.
     */
    public static string $orderByDir = 'asc';

    /**
     * Indicates whether the resource is globally searchable.
     */
    public static bool $globallySearchable = false;

    /**
     * The number of records to query when global searching.
     */
    public static int $globalSearchResultsLimit = 5;

    /**
     * Indicates the global search action. (presentable, float)
     */
    public static string $globalSearchAction = 'presentable';

    /**
     * The resource displayable icon.
     */
    public static ?string $icon = null;

    /**
     * Indicates whether the resource fields are customizeable.
     */
    public static bool $fieldsCustomizable = false;

    /**
     * Indicates whether the resource has Zapier hooks.
     */
    public static bool $hasZapierHooks = false;

    /**
     * The model the resource is related to.
     *
     * @var \Modules\Core\App\Models\Model|null
     */
    public static string $model;

    /**
     * The underlying model resource instance.
     *
     * @var \Modules\Core\App\Models\Model|null
     */
    public $resource;

    /**
     * Indicates whether the resource has detail view.
     */
    public static bool $hasDetailView = false;

    protected static array $registered = [];

    /**
     * Initialize new Resource class
     */
    public function __construct()
    {
        $this->registerIfNotRegistered();
    }

    /**
     * Get the resource underlying model class name
     *
     * @return string
     */
    public static function model()
    {
        return static::$model;
    }

    /**
     * Set the resource model instance
     *
     * @param  \Modules\Core\App\Models\Model|null  $resource
     */
    public function setModel($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get a fresh instance of the resource model.
     *
     * @return \Modules\Core\App\Models\Model
     */
    public function newModel(array $attributes = [])
    {
        $model = static::$model;

        return new $model($attributes);
    }

    /**
     * Provide the resource available cards
     */
    public function cards(): array
    {
        return [];
    }

    /**
     *  Resolve the filters intended for the resource.
     *
     * @return \Illuminate\Support\Collection<object, \Modules\Core\App\Filters\Filter>
     */
    public function resolveFilters(ResourceRequest $request)
    {
        return $this->resolveBaseFilters($request)->merge(
            $this->getFiltersFromCustomFields()
        );
    }

    /**
     * Get filters from the resource custom fields.
     */
    public function getFiltersFromCustomFields(): array
    {
        return (new CustomFieldFactory($this->name()))->createFiltersFromFields();
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): ?string
    {
        return null;
    }

    /**
     * Create JSON Resource
     *
     * @return mixed
     */
    public function createJsonResource(mixed $data, bool $resolve = false, ?ResourceRequest $request = null)
    {
        $collection = is_countable($data);

        if ($collection) {
            $resource = $this->jsonResource()::collection($data);
        } else {
            $jsonResource = $this->jsonResource();
            $resource = new $jsonResource($data);
        }

        if ($resolve) {
            $request = $request ?: app(ResourceRequest::class)->setResource($this->name());

            if (! $collection) {
                $request->setResourceId($data->getKey());
            }

            return $resource->resolve($request);
        }

        return $resource;
    }

    /**
     * Get the fields that should be included in JSON resource
     *
     * @param  \Modules\Core\App\Resource\Http\Request  $request  *
     *                                                            Indicates whether the current user can see the model in the JSON resource
     * @param  bool  $canSeeResource
     */
    public function getFieldsForJsonResource($canSeeResource = true): array
    {
        $fields = Cache::store('array')
            ->rememberForever($this->name().'json-resource-fields', function () {
                return $this->resolveFields()->withoutZapierExcluded()->all();
            });

        $result = array_filter($fields, function (Field $field) use ($canSeeResource) {
            return $canSeeResource || $field->alwaysInJsonResource === true;
        });

        return array_values($result);

    }

    /**
     * Provide the available resource fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [];
    }

    /**
     * Provide the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [];
    }

    /**
     * Provide the resource rules available only for create
     */
    public function createRules(ResourceRequest $request): array
    {
        return [];
    }

    /**
     * Provide the resource rules available only for update
     */
    public function updateRules(ResourceRequest $request): array
    {
        return [];
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): ?string
    {
        return null;
    }

    /**
     * Provide the resource relationship name when it's associated
     */
    public function associateableName(): ?string
    {
        return null;
    }

    /**
     * Provide the menu items for the resource
     */
    public function menu(): array
    {
        return [];
    }

    /**
     * Provide the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [];
    }

    /**
     * Register permissions for the resource
     */
    public function registerPermissions(): void
    {
    }

    /**
     * Get the custom validation messages for the resource
     * Useful for resources without fields.
     */
    public function validationMessages(): array
    {
        return [];
    }

    /**
     * Determine whether the resource is associateable.
     */
    public function isAssociateable(): bool
    {
        return ! is_null($this->associateableName());
    }

    /**
     * Check whether the given resource can be associated to the current resource
     */
    public function canBeAssociatedTo(Resource|string $resource): bool
    {
        $name = $resource instanceof Resource ? $resource->name() : $resource;

        return (bool) $this->associateableResources()->first(
            fn (Resource $resource) => $resource->name() == $name
        );
    }

    /**
     * Get the resource available associateable resources.
     *
     * @return \Illuminate\Support\Collection<string, \Modules\Core\App\Resource\Resource>
     */
    public function associateableResources()
    {
        return Cache::store('array')->rememberForever($this->name().'-associateables', function () {
            return Innoclapps::registeredResources()
                ->filter(fn (Resource $resource) => $resource->isAssociateable())
                ->filter(fn (Resource $resource) => $this->newModel()->isRelation($resource->associateableName()))
                ->values()
                ->mapWithKeys(fn (Resource $resource) => [$resource->associateableName() => $resource]);
        });
    }

    /**
     * Get the resource associateable relations.
     *
     * @return string[]
     */
    public function associateableRelations(): array
    {
        return static::associateableResources()->keys()->all();
    }

    /**
     * Get the resource available custom fields.
     */
    public function customFields(): CustomFieldCollection
    {
        return Cache::store('array')->rememberForever(static::name().'-customfields', function () {
            return CustomFieldFileCache::get()->where('resource_name', static::name());
        });
    }

    /**
     * Get the resource search columns.
     */
    public function searchableColumns(): array
    {
        return $this->resolveFields()->toSearchableColumns();
    }

    /**
     * Get columns that should be used for global search.
     */
    public function globalSearchColumns(): array
    {
        return $this->resolveFields()
            ->filter(function (Field $field) {
                return $field->isCustomField() ? $field->isUnique() : true;
            })
            ->toSearchableColumns();
    }

    /**
     * Determine if this resource is searchable
     */
    public function searchable(): bool
    {
        return count($this->searchableColumns()) > 0;
    }

    /**
     * Get the displayable label of the resource
     */
    public static function label(): string
    {
        return Str::plural(Str::title(Str::snake(class_basename(get_called_class()), ' ')));
    }

    /**
     * Get the displayable singular label of the resource
     */
    public static function singularLabel(): string
    {
        return Str::singular(static::label());
    }

    /**
     * Get the internal name of the resource
     */
    public static function name(): string
    {
        return Str::plural(Str::kebab(class_basename(get_called_class())));
    }

    /**
     * Get the internal singular name of the resource
     */
    public static function singularName(): string
    {
        return Str::singular(static::name());
    }

    /**
     * Get the resource importable class
     */
    public function importable(): Import
    {
        return new Import($this);
    }

    /**
     * Get the resource import sample class
     */
    public function importSample(int $totalRows = 1): ImportSample
    {
        return new ImportSample($this, $totalRows);
    }

    /**
     * Get the resource export class.
     */
    public function exportable(Builder $query): Export
    {
        return new Export($this, $query);
    }

    /**
     * Register the resource available menu items
     */
    protected function registerMenuItems(): void
    {
        foreach ($this->menu() as $item) {
            if (! $item->singularName) {
                $item->singularName($this->singularLabel());
            }

            Menu::register($item);
        }
    }

    /**
     * Register the resource settings menu items
     */
    protected function registerSettingsMenuItems(): void
    {
        foreach ($this->settingsMenu() as $key => $item) {
            SettingsMenu::register($item, is_int($key) ? $this->name() : $key);
        }
    }

    /**
     * Register the resource available cards
     */
    protected function registerCards(): void
    {
        Cards::register($this->name(), $this->cards(...));
    }

    /**
     * Register the resource available CRUD fields
     */
    protected function registerFields(): void
    {
        Fields::group($this->name(), function () {
            $resourceFields = $this->fields(app(ResourceRequest::class)->setResource($this->name()));

            return array_merge($resourceFields, $this->customFields()->map(
                fn (CustomField $field) => CustomFieldFactory::createInstance($field)
            )->all());
        });
    }

    /**
     * Register common permissions for the resource
     */
    protected function registerCommonPermissions(): void
    {
        if ($callable = config('core.resources.permissions.common')) {
            (new $callable)($this);
        }
    }

    /**
     * Clear the registered resource.
     */
    public static function clearRegisteredResources(): void
    {
        static::$registered = [];
    }

    /**
     * Register the resource if not registered.
     */
    protected function registerIfNotRegistered(): void
    {
        if (! isset(static::$registered[static::class])) {
            $this->register();

            static::$registered[static::class] = true;
        }
    }

    /**
     * Register the resource information
     */
    protected function register(): void
    {
        $this->registerPermissions();
        $this->registerCards();

        if ($this instanceof HasOperations) {
            $this->registerFields();
        }

        Innoclapps::booting(function () {
            $this->registerMenuItems();
            $this->registerSettingsMenuItems();
        });
    }

    /**
     * Get the request criteria for the resource.
     */
    public function getRequestCriteria(ResourceRequest $request, ?array $searchableColumns = null): RequestCriteria
    {
        return (new RequestCriteria($request))->setSearchFields(
            $searchableColumns ?? $this->searchableColumns()
        );
    }

    /**
     * Get the filters criteria for the given request.
     */
    public function getFiltersCriteria(ResourceRequest $request, string $rulesKey = 'rules'): FilterRulesCriteria
    {
        return new FilterRulesCriteria(
            $request->get($rulesKey, []),
            $this->resolveFilters($request),
            $request
        );
    }

    /**
     * Perform the model creation.
     */
    protected function performCreate(Model $model, ResourceRequest $request): Model
    {
        $this->beforeCreate($model, $request);

        $model->save();

        // Executed event when not wrapped in a transaction callback.
        DB::afterCommit(function () use ($model, $request) {
            if ($callbacks = $request->getCallbacks()) {
                $callbacks->each->__invoke($model, $request);
            }

            $this->afterCreate($model, $request);

            ResourceRecordCreated::dispatch($model, $this);
        });

        return $model;
    }

    /**
     * Create new resource record in storage.
     */
    public function create(Model $model, ResourceRequest $request): Model
    {
        return $this->performCreate($model, $request);
    }

    /**
     * Perform the model update.
     */
    protected function performUpdate(Model $model, ResourceRequest $request): Model
    {
        $this->beforeUpdate($model, $request);

        $model->save();

        // Executed event when not wrapped in a transaction callback.
        DB::afterCommit(function () use ($model, $request) {
            if ($callbacks = $request->getCallbacks()) {
                $callbacks->each->__invoke($model, $request);
            }

            $this->afterUpdate($model, $request);

            ResourceRecordUpdated::dispatch($model, $this);
        });

        return $model;
    }

    /**
     * Update resource record in storage.
     */
    public function update(Model $model, ResourceRequest $request): Model
    {
        return $this->performUpdate($model, $request);
    }

    /**
     * Delete resource record.
     */
    public function delete(Model $model, ResourceRequest $request): mixed
    {
        DB::beginTransaction();

        $model->delete();

        ResourceRecordDeleted::dispatch($model, $this);

        DB::commit();

        return '';
    }

    /**
     * Serialize the resource
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'singularName' => $this->singularName(),
            'label' => $this->label(),
            'singularLabel' => $this->singularLabel(),
            'icon' => static::$icon,
            'globallySearchable' => static::$globallySearchable,
            'fieldsCustomizable' => static::$fieldsCustomizable,
            'acceptsUniqueCustomFields' => $this instanceof AcceptsUniqueCustomFields,
            'hasDetailView' => static::$hasDetailView,
        ];
    }
}
