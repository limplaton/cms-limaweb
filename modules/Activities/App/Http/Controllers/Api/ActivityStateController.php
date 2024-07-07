<?php
 

namespace Modules\Activities\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Activities\App\Http\Resources\ActivityResource;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Http\Controllers\ApiController;

class ActivityStateController extends ApiController
{
    /**
     * Mark activity as complete.
     *
     * @deprecated Use regular activity update with "is_completed" attribute.
     */
    public function complete(Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity->markAsComplete();

        return $this->response(
            new ActivityResource($activity->resource()->displayQuery()->find($activity->id))
        );
    }

    /**
     * Mark activity as incomplete.
     *
     * @deprecated Use regular activity update with "is_completed" attribute.
     */
    public function incomplete(Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity->markAsIncomplete();

        return $this->response(
            new ActivityResource($activity->resource()->displayQuery()->find($activity->id))
        );
    }
}
