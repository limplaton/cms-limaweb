<?php
 

namespace Modules\Contacts\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Activities\App\Http\Resources\ActivityResource;
use Modules\Calls\App\Http\Resources\CallResource;
use Modules\Core\App\Http\Resources\ChangelogResource;
use Modules\Core\App\Http\Resources\MediaResource;
use Modules\Core\App\Resource\JsonResource;
use Modules\MailClient\App\Http\Resources\EmailAccountMessageResource;
use Modules\Notes\App\Http\Resources\NoteResource;

/** @mixin \Modules\Contacts\App\Models\Contact */
class ContactResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        ChangelogResource::topLevelResource($this->resource);

        return $this->withCommonData([
            'notes_count' => $this->whenCounted('notes', fn () => (int) $this->notes_count),
            'calls_count' => $this->whenCounted('calls', fn () => (int) $this->calls_count),
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'uploaded_avatar_url' => $this->uploaded_avatar_url,
            $this->mergeWhen(! $request->isZapier(), [
                'guest_email' => $this->getGuestEmail(),
                'guest_display_name' => $this->getGuestDisplayName(),
            ]),
            $this->mergeWhen(! $request->isZapier() && $this->userCanViewCurrentResource(), [
                'changelog' => ChangelogResource::collection($this->whenLoaded('changelog')),
                'notes' => NoteResource::collection($this->whenLoaded('notes')),
                'calls' => CallResource::collection($this->whenLoaded('calls')),
                'activities' => ActivityResource::collection($this->whenLoaded('activities')),
                'media' => MediaResource::collection($this->whenLoaded('media')),
                'emails' => EmailAccountMessageResource::collection($this->whenLoaded('emails')),
            ]),
        ], $request);
    }
}
