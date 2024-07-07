<?php
 

namespace Modules\Deals\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Deals\App\Http\Resources\StageResource;
use Modules\Deals\App\Models\Pipeline;
use Modules\Deals\App\Models\Stage;

class PipelineStageController extends ApiController
{
    /**
     * Retrieve pipeline stages.
     */
    public function index(Pipeline $pipeline, Request $request): JsonResponse
    {
        $this->authorize('view', $pipeline);

        return $this->response(
            StageResource::collection(
                Stage::where('pipeline_id', $pipeline->id)->paginate($request->integer('per_page') ?: null)
            )
        );
    }
}
