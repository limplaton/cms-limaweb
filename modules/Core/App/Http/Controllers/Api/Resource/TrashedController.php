<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\TrashedResourceRequest;

class TrashedController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(TrashedResourceRequest $request): JsonResponse
    {
        $this->authorize('viewAny', $request->resource()::$model);

        $query = $request->resource()->trashedIndexQuery($request);

        $results = $query->paginate($request->integer('per_page') ?: null);

        return $this->response($request->toResponse($results));
    }

    /**
     * Perform search on the trashed resource.
     */
    public function search(TrashedResourceRequest $request): JsonResponse
    {
        $resource = $request->resource();

        abort_if(! $resource->searchable(), 404);

        if (empty($request->q)) {
            return $this->response([]);
        }

        $query = $request->resource()->trashedSearchQuery($request);

        return $this->response(
            $request->toResponse($query->get())
        );
    }

    /**
     * Display resource record.
     */
    public function show(TrashedResourceRequest $request): JsonResponse
    {
        $this->authorize('view', $request->record());

        $result = $request->resource()->trashedDisplayQuery()->findOrFail($request->resourceId());

        return $this->response($request->toResponse($result));
    }

    /**
     * Remove resource record from storage.
     */
    public function destroy(TrashedResourceRequest $request): JsonResponse
    {
        $this->authorize('delete', $request->record());

        DB::transaction(function () use ($request) {
            $request->record()->forceDelete();
        });

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Restore the soft deleted record.
     */
    public function restore(TrashedResourceRequest $request): JsonResponse
    {
        $this->authorize('view', $request->record());

        $request->record()->restore();

        return $this->response($request->toResponse(
            $request->resource()->displayQuery()->find($request->resourceId())
        ));
    }
}
