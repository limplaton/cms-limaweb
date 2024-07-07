<?php
 

namespace Modules\Core\App\Resource;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use JsonSerializable;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Http\Resources\JsonResource as BaseJsonResource;

/** @mixin \Modules\Core\App\Models\Model */
class JsonResource extends BaseJsonResource
{
    /**
     * Top level resource actions
     *
     * @var null|\Illuminate\Support\Collection|array
     */
    protected $actions;

    /**
     * Provide common data for the resource
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    protected function withCommonData(array $data, $request): array
    {
        $data = parent::withCommonData($data, $request);

        if ($resource = Innoclapps::resourceByModel($this->resource)) {
            $data = $this->mergeFieldsFromResource($resource, $data);
        }

        if (! $request->isZapier()) {
            if (! is_null($this->actions)) {
                $data['actions'] = $this->actions;
            }

            if ($resource) {
                $data = $this->mergeAssociationsCount($data);
            }
        }

        return $data;
    }

    /**
     * Merge the request fields in the resource
     */
    protected function mergeFieldsFromResource(Resource $resource, array $data): array
    {
        $fields = $resource->getFieldsForJsonResource(
            $this->userCanViewCurrentResource()
        );

        $fieldsData = [];

        foreach ($fields as $field) {
            $fieldData = $field->resolveForJsonResource($this->resource);

            if (! is_null($fieldData)) {
                $fieldsData[] = $fieldData;
            }
        }

        return array_merge($data, ...$fieldsData);
    }

    /**
     * Merge the associations count key.
     */
    protected function mergeAssociationsCount($data): array
    {
        $countedAssociations = collect($this->associateableRelations())->map(
            fn (string $relation) => (string) Str::of($relation)->snake()->finish('_count')
        );

        $allCounted = $countedAssociations->every(
            fn (string $attribute) => isset($this->resource->getAttributes()[$attribute])
        );

        if ($allCounted) {
            $data['associations_count'] = $countedAssociations->reduce(function (?int $count, string $attribute) {
                return $count + (int) $this->getAttributes()[$attribute];
            });
        }

        return $data;
    }

    /**
     * Check whether the current user can see the current resource.
     */
    protected function userCanViewCurrentResource(): bool
    {
        // The wasRecentlyCreated check is performed for new main resource
        // e.q. in the front end the main resource is still shown after is created
        // to the user even if don't have permissions to view
        // e.q. regular user created a company and assigned to another user
        // after the company is assigned to another user, the creator won't
        // be able to see the company directly after creation
        // Because the front end still shows the fully create company after creation
        // In this case, to prevent showing 403 immediately we show the user the entity but after he navigated from
        // the entity view or tried to update, it will show the 403 error
        if ($this->wasRecentlyCreated) {
            return true;
        }

        /** @var \Modules\Users\App\Models\User */
        $user = Auth::user();

        return $user->can('view', $this->resource);
    }

    /**
     * Set top level resource actions (only for single models)
     *
     * @param  \Illuminate\Support\Collection|array  $actions
     */
    public function withActions($actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Resolve the resource to an array.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return array
     */
    public function resolve($request = null)
    {
        $data = $this->toArray(
            $request = $request ?: $this->newResourceRequest()
        );

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array) $data);
    }

    /**
     * Transform the resource into an HTTP response.
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($request = null)
    {
        return $this->toResponse(
            $request ?: $this->newResourceRequest()
        );
    }

    /**
     * Prepare the resource for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->resolve($this->newResourceRequest());
    }

    protected function newResourceRequest(): ResourceRequest
    {
        return app(ResourceRequest::class)->setResource($this->resource->resource()->name());
    }
}
