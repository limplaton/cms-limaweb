<?php
 

namespace Modules\Deals\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Deals\App\Models\Pipeline;

class UpdatePipelineDisplayOrder extends ApiController
{
    /**
     * Save the pipelines display order.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'order.*.id' => 'required|int',
            'order.*.display_order' => 'required|int',
        ]);

        foreach ($request->input('order', []) as $data) {
            Pipeline::find($data['id'])->saveUserDisplayOrder($request->user(), $data['display_order']);
        }

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
