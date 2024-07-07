<?php
 

namespace Modules\Billable\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Billable\App\Http\Resources\ProductResource;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;

class ActiveProductController extends ApiController
{
    /**
     * Search for active products
     */
    public function handle(ResourceRequest $request): JsonResponse
    {
        abort_if($request->resource()->name() !== 'products', 404);

        $products = $request->resource()
            ->indexQuery($request)
            ->active()
            ->get();

        return $this->response(ProductResource::collection($products));
    }
}
