<?php
 

namespace Modules\Comments\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Http\Resources\JsonResource;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\Comments\App\Models\Comment */
class CommentResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'body' => clean($this->body),
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
        ], $request);
    }
}
