<?php
 

namespace Modules\Deals\App\Fields;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\ConfiguresOptions;
use Modules\Core\App\Fields\MorphToMany;
use Modules\Core\App\Fields\Selectable;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Support\HasOptions;
use Modules\Core\App\Table\MorphToManyColumn;
use Modules\Deals\App\Http\Resources\DealResource;
use Modules\Deals\App\Resources\Deal;

class Deals extends MorphToMany
{
    use ConfiguresOptions, HasOptions, Selectable;

    /**
     * Multi select custom component
     */
    public static $component = 'select-multiple-field';

    public ?int $order = 999;

    protected static Deal $resource;

    /**
     * Create new instance of Deals field
     *
     * @param  string  $relation
     * @param  string  $label  Custom label
     */
    public function __construct($relation = 'deals', $label = null)
    {
        parent::__construct($relation, $label ?? __('deals::deal.deals'));

        static::$resource = Innoclapps::resourceByName('deals');

        $this->labelKey('name')
            ->valueKey('id')
            ->excludeFromExport()
            ->excludeFromImport()
            ->onOptionClick('float', ['resourceName' => 'deals'])
            ->eachOnNewLine()
            ->excludeFromZapierResponse()
            ->async('/deals/search')
            ->lazyLoad('/deals', ['order' => 'created_at|desc'])
            ->provideSampleValueUsing(fn () => [1, 2])
            ->fillUsing(function (Model $model, string $attribute, ResourceRequest $request, mixed $value) {
                return ! is_null($value) ? $this->fillCallback($model, $this->parseValue($value, $request)) : null;
            })->resolveForJsonResourceUsing(function (Model $model, string $attribute) {
                if ($model->relationLoaded($this->morphToManyRelationship)) {
                    return [
                        $attribute => DealResource::collection($this->resolve($model)),
                    ];
                }
            });
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): MorphToManyColumn
    {
        return (new MorphToManyColumn(
            $this->morphToManyRelationship,
            $this->labelKey,
            $this->label
        ))->wrap();
    }

    /**
     * Parse the given value for storage.
     */
    protected function parseValue($value, ResourceRequest $request): Collection
    {
        // Perhaps int e.q. when ID provided?
        $value = is_string($value) ? explode(',', $value) : Arr::wrap($value);
        $collection = new Collection([]);

        foreach ($value as $id) {
            if ($model = $this->getModelFromValue($id, $request)) {
                $collection->push($model);
            }
        }

        return $collection;
    }

    /**
     * Get model instance from the given ID and ensure it's authorized to view before syncing.
     */
    protected function getModelFromValue(int|string|null $value, ResourceRequest $request): ?EloquentModel
    {
        // ID provided?
        if (is_numeric($value)) {
            $model = static::$resource->newQuery()->find($value);
        } elseif ($value) {
            $model = static::$resource->newQueryWithTrashed()->where('name', trim($value))->first();

            if ($model?->trashed()) {
                $model->restore();
            }
        }

        return $model && $request->user()->can('view', $model) ? $model : null;
    }

    /**
     * Get the fill callback.
     */
    protected function fillCallback(Model $model, Collection $ids)
    {
        return function () use ($model, $ids) {
            if ($model->wasRecentlyCreated) {
                if (count($ids) > 0) {
                    $model->{$this->morphToManyRelationship}()->attach($ids);
                }
            } else {
                $model->{$this->morphToManyRelationship}()->sync($ids);
            }
        };
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'options' => [],
            'labelKey' => $this->labelKey,
            'valueKey' => $this->valueKey,
        ]);
    }
}
