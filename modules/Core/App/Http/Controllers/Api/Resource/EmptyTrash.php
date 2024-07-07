<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\TrashedResourceRequest;
use Modules\Core\App\Models\Model;

class EmptyTrash extends ApiController
{
    /**
     * Empth the resource trashed records.
     *
     * The request must be made in batches until there are no records available.
     * 99% of customers does not use queue, hence, we cannot queue a job.
     */
    public function __invoke(TrashedResourceRequest $request): JsonResponse
    {
        $totalDeleted = 0;

        DB::transaction(function () use ($request, &$totalDeleted) {
            $request->resource()
                ->trashedIndexQuery($request)
                ->limit($request->integer('limit', 500))
                ->get()
                ->filter(fn ($model) => $request->user()->can('bulkDelete', $model))
                ->each(function (Model $model) use (&$totalDeleted) {
                    if ($model->forceDelete()) {
                        $totalDeleted++;
                    }
                });
        });

        return $this->response(['deleted' => $totalDeleted]);
    }
}
