<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;

class SearchController extends ApiController
{
    /**
     * Perform search for a resource.
     */
    public function handle(ResourceRequest $request): JsonResponse
    {
        /** @var \Modules\Core\App\Resource\Resource */
        $resource = tap($request->resource(), function ($resource) {
            abort_if(! $resource->searchable(), 404);
        });

        if (empty($request->q)) {
            return $this->response([]);
        }

        $query = $resource->searchQuery($request);

        return $this->response(
            $request->toResponse($query->get())
        );
    }
}
