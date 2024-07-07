<?php
 

namespace Modules\Notes\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Comments\App\Http\Resources\CommentResource;
use Modules\Contacts\App\Http\Resources\CompanyResource;
use Modules\Contacts\App\Http\Resources\ContactResource;
use Modules\Core\App\Http\Resources\MediaResource;
use Modules\Core\App\Resource\JsonResource;
use Modules\Deals\App\Http\Resources\DealResource;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\Notes\App\Models\Note */
class NoteResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'body' => clean($this->body),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'companies' => CompanyResource::collection($this->whenLoaded('companies')),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'deals' => DealResource::collection($this->whenLoaded('deals')),
            $this->mergeWhen(! $request->isZapier(), [
                'comments' => CommentResource::collection($this->whenLoaded('comments')),
                'comments_count' => (int) $this->comments_count ?: 0,
                // Not used by the front-end, API can upload and use media in notes by providing with=media in URL parameter
                'media' => MediaResource::collection($this->whenLoaded('media')),
            ]),
        ], $request);
    }
}
