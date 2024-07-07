<?php
 

namespace Modules\Core\App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;

/** @mixin \Modules\Core\App\Http\Requests\ResourceRequest */
trait InteractsWithResources
{
    /**
     * Custom resource id for the request.
     */
    protected null|int|string $customResourceId = null;

    /**
     * Custom resource for the request.
     */
    protected ?string $customResource = null;

    /**
     * Resource for the request.
     */
    protected ?Resource $resource = null;

    /**
     * The request resource record.
     */
    protected ?Model $record = null;

    /**
     * Get the resource name for the current request.
     */
    public function resourceName(): ?string
    {
        return $this->customResource ?: $this->route('resource');
    }

    /**
     * Set custom resource for the request.
     */
    public function setResource(string $name): static
    {
        $this->customResource = $name;

        $this->resource = null;
        $this->record = null;

        return $this;
    }

    /**
     * Get the request resource id.
     */
    public function resourceId(): int|string|null
    {
        return $this->customResourceId ?: $this->route('resourceId');
    }

    /**
     * Set custom resource id for the request.
     */
    public function setResourceId(int|string $id): static
    {
        $this->customResourceId = $id;

        $this->record = null;

        return $this;
    }

    /**
     * Get the class of the resource being requested.
     */
    public function resource(): ?Resource
    {
        if (! $this->resource) {
            $this->resource = $this->findResource($this->resourceName());
        }

        return $this->resource;
    }

    /**
     * Get the resource record for the current request.
     */
    public function record(): Model
    {
        return $this->record ??= $this->newQuery()->findOrFail($this->resourceId());
    }

    /**
     * Manually set the record for the current update request.
     */
    public function setRecord(Model $record): static
    {
        $this->record = $record;

        return $this;
    }

    /**
     * Get new query from the current resource.
     */
    public function newQuery(): Builder
    {
        return $this->resource()->newQuery();
    }

    /**
     * Get resource by a given name.
     */
    public function findResource(?string $name): ?Resource
    {
        if (! $name) {
            return null;
        }

        return Innoclapps::resourceByName($name);
    }
}
