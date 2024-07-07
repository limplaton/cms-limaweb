<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Contracts\Resources\Cloneable;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;

class CloneController extends ApiController
{
    /**
     * Clone a resource record
     */
    public function handle(ResourceRequest $request): JsonResponse
    {
        /** @var \Modules\Core\App\Resource\Resource&\Modules\Core\App\Contracts\Resources\Cloneable */
        $resource = $request->resource();

        abort_unless($resource instanceof Cloneable, 404);

        $this->authorize('view', $request->record());

        $record = $resource->clone($request->record(), (int) $request->user()->getKey());

        return $this->response($request->toResponse(
            $resource->displayQuery()->find($record->getKey())
        ));
    }
}
