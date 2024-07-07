<?php
 

namespace Modules\MailClient\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Http\Resources\JsonResource;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\MailClient\App\Models\PredefinedMailTemplate */
class PredefinedMailTemplateResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'is_shared' => $this->is_shared,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
        ], $request);
    }
}
