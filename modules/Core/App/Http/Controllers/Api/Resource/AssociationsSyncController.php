<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;

class AssociationsSyncController extends ApiController
{
    /**
     * Associate records to resource.
     */
    public function attach(ResourceRequest $request): JsonResponse
    {
        $this->authorize('update', $request->record());

        $this->validateProvidedResources($request);

        $totalUnauthorized = 0;

        $resources = $request->keys();

        foreach ($resources as $resourceName) {
            $ids = $request->input($resourceName);

            if (! is_array($ids) || count($ids) === 0) {
                continue;
            }

            $relatedResource = $request->findResource($resourceName);

            $relatedRecords = $this->filterUnauthorizedViewModels($relatedResource->newModel()->findMany($ids));

            if ($relatedRecords->isEmpty()) {
                $totalUnauthorized++;

                continue;
            }

            $result = $request->record()
                ->{$relatedResource->associateableName()}()
                ->syncWithoutDetaching($relatedRecords->modelKeys());

            // When passing only 1 record for associations
            // Show a conflict error message that this record is already associated
            if (count($result['attached']) === 0 &&
                        count($resources) === 1 &&
                        count($ids) == 1) {
                return $this->response(['message' => __('core::resource.already_associated')], 409);
            }
        }

        if ($totalUnauthorized === count($resources)) {
            abort(403, 'You are not authorized to perform this action.');
        }

        return $this->response($request->toResponse(
            $request->resource()->displayQuery()->find($request->resourceId())
        ));
    }

    /**
     * Dissociate records from resource.
     */
    public function detach(ResourceRequest $request): JsonResponse
    {
        $this->authorize('update', $request->record());

        $this->validateProvidedResources($request);

        foreach ($request->keys() as $resourceName) {
            $ids = $request->input($resourceName);

            if (! is_array($ids) || count($ids) === 0) {
                continue;
            }

            $request->record()
                ->{$request->findResource($resourceName)->associateableName()}()
                ->detach($ids);
        }

        return $this->response($request->toResponse(
            $request->resource()->displayQuery()->find($request->resourceId())
        ));
    }

    /**
     * Sync records for resource.
     */
    public function sync(ResourceRequest $request): JsonResponse
    {
        $this->authorize('update', $record = $request->record());

        $this->validateProvidedResources($request);

        foreach ($request->keys() as $resourceName) {
            $ids = $request->input($resourceName);

            if (! is_array($ids)) {
                continue;
            }

            $relatedResource = $request->findResource($resourceName);

            if (count($ids) === 0) {
                // No associations, detach all
                $request->record()
                    ->{$relatedResource->associateableName()}()
                    ->detach(
                        $this->filterUnauthorizedViewModels($record->{$relatedResource->associateableName()})->modelKeys()
                    );

                continue;
            }

            $request->record()
                ->{$relatedResource->associateableName()}()
                ->sync(
                    $this->filterUnauthorizedViewModels($relatedResource->newModel()->findMany($ids))->modelKeys()
                );
        }

        return $this->response($request->toResponse(
            $request->resource()->displayQuery()->find($request->resourceId())
        ));
    }

    /**
     * Filter the unauthorized models.
     */
    protected function filterUnauthorizedViewModels(Collection $models): Collection
    {
        return $models->reject(fn ($model) => Gate::denies('view', $model));
    }

    /**
     * Validate the given resources.
     */
    protected function validateProvidedResources(ResourceRequest $request): void
    {
        foreach ($request->keys() as $resource) {
            $relatedResource = $request->findResource($resource);

            if (! $relatedResource ||
                ! $relatedResource->canBeAssociatedTo($request->resource())) {
                $message = "The provided resource name \"$resource\" cannot be associated to {$request->resource()->singularName()}.";
                abort(409, $message);
            }
        }
    }
}
