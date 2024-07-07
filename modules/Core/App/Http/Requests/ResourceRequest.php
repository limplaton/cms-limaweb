<?php
 

namespace Modules\Core\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Models\Model;

class ResourceRequest extends FormRequest
{
    use InteractsWithResources;

    /**
     * The remembered hydrate callbacks.
     */
    protected array $hydrateCallbacks = [];

    /**
     * The original request data.
     */
    protected array $original = [];

    /**
     * Resolve the resource json resource and create appropriate response.
     */
    public function toResponse(mixed $data): mixed
    {
        if (! $this->resource()->jsonResource()) {
            return $data;
        }

        /** @var \Modules\Core\App\Resource\JsonResource */
        $jsonResource = $this->resource()->createJsonResource($data);

        if ($data instanceof Model) {
            $jsonResource->withActions($this->resource()->resolveActions($this));
        }

        return $jsonResource->toResponse($this)->getData();
    }

    /**
     * Get a new hydrated model from the fields.
     */
    public function newHydratedModel(array $defaults = []): Model
    {
        return $this->hydrateModel($this->resource()->newModel($defaults));
    }

    /**
     * Hydrate the model from resource fields fields.
     */
    public function hydrateModel(Model $model): Model
    {
        $this->toFields()->each(function (Field $field) use (&$model) {
            $callback = $field->fill(
                $model,
                $field->attribute,
                $this,
                $field->requestAttribute()
            );

            if (is_callable($callback)) {
                $this->hydrateCallbacks[] = $callback;
            }
        });

        return $model;
    }

    /**
     * Get the callbacks from the hydration.
     */
    public function getCallbacks(): Collection
    {
        return collect($this->hydrateCallbacks);
    }

    /**
     * Get the callbacks from the hydration.
     */
    public function setCallbacks(array|Collection $callbacks)
    {
        $this->hydrateCallbacks = is_array($callbacks) ? $callbacks : $callbacks->all();

        return $this;
    }

    /**
     * Check whether the current request is for create.
     */
    public function isCreateRequest(): bool
    {
        return $this->intent == 'create' || $this instanceof CreateResourceRequest;
    }

    /**
     * Check whether the current request is for update.
     */
    public function isUpdateRequest(): bool
    {
        return in_array($this->intent, ['update', 'detail']) || $this instanceof UpdateResourceRequest;
    }

    /**
     * Check whether the current request is for import.
     */
    public function isImportRequest(): bool
    {
        return $this instanceof ImportRequest;
    }

    /**
     * Check whether the current request is via resource.
     */
    public function viaResource(): bool
    {
        return $this->has('via_resource');
    }

    /**
     * Get all the available fields for the resource.
     */
    public function allFields(): FieldsCollection
    {
        return $this->resource()->setModel(
            $this->resourceId() ? $this->record() : null
        )->getFields();
    }

    /**
     * Find record for the currently set resource from unique custom fields.
     */
    public function findRecordFromUniqueCustomFields(bool $withTrashed = false): ?Model
    {
        $attributes = $this->allFields()
            ->filterCustomFields()
            ->filter(fn (Field $field) => $field->customField->is_unique)
            ->toData($this);

        if (count($attributes) === 0) {
            return null;
        }

        $query = $withTrashed ? $this->resource()->newQueryWithTrashed() : $this->resource->newQuery();

        $query->where(function ($query) use ($attributes) {
            foreach ($attributes as $attribute => $value) {
                $query->orWhere($attribute, $value);
            }
        });

        return $query->first();
    }

    /**
     * Get the original request data before any validation callbacks.
     */
    public function original(?string $key = null): mixed
    {
        return is_string($key) ? $this->original[$key] ?? null : $this->original;
    }

    /**
     * Set the original request data before any validation callbacks.
     */
    public function setOriginal(array $input): static
    {
        $this->original = $input;

        return $this;
    }

    /**
     * Convert the request to an instance of "CreateResourceRequest".
     */
    public function asCreateRequest(): CreateResourceRequest
    {
        // the current request must use the "InteractsWithResourceFields" trait.

        $request = parent::createFrom($this, new CreateResourceRequest);

        $request->setResource($this->resource()->name())
            ->setFields($this->getFields())
            ->setCallbacks($this->getCallbacks())
            ->setOriginal($this->original());

        return $request;
    }

    /**
     * Convert the request to an instance of "UpdateResourceRequest".
     */
    public function asUpdateRequest(Model $record): UpdateResourceRequest
    {
        // the current request must use the "InteractsWithResourceFields" trait.

        $request = parent::createFrom($this, new UpdateResourceRequest);

        $request->setResource($this->resource()->name())
            ->setRecord($record)
            ->setResourceId($record->getKey())
            ->setFields($this->getFields())
            ->setCallbacks($this->getCallbacks())
            ->setOriginal($this->original());

        return $request;
    }
}
