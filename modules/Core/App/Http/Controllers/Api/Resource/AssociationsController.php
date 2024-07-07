<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Modules\Core\App\Common\Timeline\Timelineables;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;

class AssociationsController extends ApiController
{
    /**
     * Get the all of the resource associations.
     */
    public function index(ResourceRequest $request): JsonResponse
    {
        $response = [];

        foreach ($request->resource()->associateableResources() as $resource) {
            $associations = $resource->indexQuery($request)->whereHas(
                $request->resource()->associateableName(),
                function (Builder $query) use ($request) {
                    return $query->where($query->getModel()->getKeyName(), $request->resourceId());
                })->paginate($request->integer('per_page') ?: null);

            $response[$resource->name()] = collect(
                $resource->createJsonResource($associations, true, $request)
            )->map(function (array $data, int $index) {
                $data['is_primary_associated'] = $index === 0;

                return $data;
            })->all();
        }

        return $this->response($response);
    }

    /**
     * Get the resource given resource associated associations.
     */
    public function show(ResourceRequest $request): JsonResponse
    {
        $this->authorize('view', $request->record());

        $associatedResource = $request->findResource($request->associatedResource);

        abort_if(! $associatedResource?->isAssociateable() || ! $associatedResource->jsonResource(), 404);

        abort_if($request->isForTimeline() &&
            (
                ! Timelineables::hasTimeline($request->record()) ||
                ! Timelineables::isTimelineable($associatedResource->newModel())
            ), 404);

        if ($request->isForTimeline()) {
            $query = $associatedResource->timelineQuery($request->record(), $request);
        } else {
            $query = $associatedResource
                ->indexQuery($request)
                ->whereHas($request->resource()->associateableName(), function (Builder $query) use ($request) {
                    return $query->where($request->record()->getKeyName(), $request->resourceId());
                });
        }

        $records = $query->paginate($request->integer('per_page') ?: null);

        $associatedResource->jsonResource()::topLevelResource($request->record());

        return $this->response(
            $associatedResource->jsonResource()::collection($records)->toResponse($request)->getData()
        );
    }
}
