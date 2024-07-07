<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\OAuthAccount */
class OAuthAccountResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'user_id' => $this->user_id,
            'type' => $this->type,
            'email' => $this->email,
            'requires_auth' => $this->requires_auth,
        ], $request);
    }
}
