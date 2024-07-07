<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Models\Tag;

class UpdateTagDisplayOrder extends ApiController
{
    /**
     * Save the pipelines display order.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            '*.id' => 'required|int',
            '*.display_order' => 'required|int',
        ]);

        foreach ($request->all() as $tag) {
            Tag::find($tag['id'])->fill(['display_order' => $tag['display_order']])->save();
        }

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
