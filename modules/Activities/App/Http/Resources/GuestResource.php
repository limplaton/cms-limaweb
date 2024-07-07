<?php
 

namespace Modules\Activities\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\App\Facades\Innoclapps;

/** @mixin \Modules\Activities\App\Models */
class GuestResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->guestable->getKey(),
            'guest_email' => $this->guestable->getGuestEmail(),
            'guest_display_name' => $this->guestable->getGuestDisplayName(),
            'resource_name' => Innoclapps::resourceByModel($this->guestable)->name(),
        ];
    }
}
