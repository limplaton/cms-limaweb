<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;

class FilterRulesController extends ApiController
{
    /**
     * Get the resource available filters rules.
     */
    public function index(ResourceRequest $request): JsonResponse
    {
        return $this->response($request->resource()->resolveFilters($request));
    }
}
