<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\TagRequest;
use Modules\Core\App\Http\Resources\TagResource;
use Modules\Core\App\Models\Tag;

class TagController extends ApiController
{
    /**
     * Store new tag in storage.
     */
    public function store(string $type, TagRequest $request): JsonResponse
    {
        $tag = Tag::findOrCreate($request->name, $type);

        $tag->swatch_color = $request->swatch_color;
        $tag->save();

        return $this->response(
            new TagResource($tag),
            $tag->wasRecentlyCreated ? JsonResponse::HTTP_CREATED : JsonResponse::HTTP_OK
        );
    }

    /**
     * Update tag in storage.
     */
    public function update(Tag $tag, TagRequest $request): JsonResponse
    {
        $tag->fill([
            'name' => $request->name,
            'swatch_color' => $request->swatch_color,
            'display_order' => $request->input('display_order', $tag->display_order),
        ])->save();

        return $this->response(new TagResource($tag));
    }

    /**
     * Delete the tag from storage.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
