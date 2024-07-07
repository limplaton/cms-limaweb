<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\GlobalSearch;
use Modules\Core\App\Resource\Resource;

class GlobalSearchController extends ApiController
{
    /**
     * Perform global search.
     */
    public function handle(ResourceRequest $request): JsonResponse
    {
        if (empty($request->q)) {
            return $this->response([]);
        }

        $only = (array) $request->get('only', []);

        $resources = Innoclapps::globallySearchableResources()
            ->when(count($only) > 0, fn ($collection) => $collection->filter(
                fn (Resource $resource) => in_array($resource->name(), $only)
            ));

        return $this->response(
            new GlobalSearch($request, $resources->all())
        );
    }
}
