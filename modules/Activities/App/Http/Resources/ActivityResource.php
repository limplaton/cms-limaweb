<?php
 

namespace Modules\Activities\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Comments\App\Http\Resources\CommentResource;
use Modules\Core\App\Http\Resources\MediaResource;
use Modules\Core\App\Resource\JsonResource;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\Activities\App\Models\Activity */
class ActivityResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'is_reminded' => $this->is_reminded,
            'is_due' => $this->is_due,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            $this->mergeWhen(! $request->isZapier(), [
                'comments' => CommentResource::collection($this->whenLoaded('comments')),
                'comments_count' => (int) $this->comments_count ?: 0,
                'media' => MediaResource::collection($this->whenLoaded('media')),
            ]),
        ], $request);
    }
}
