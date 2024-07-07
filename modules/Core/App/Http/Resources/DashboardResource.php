<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\Dashboard */
class DashboardResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'is_default' => $this->is_default,
            'cards' => $this->cards,
            'user_id' => $this->user_id,
        ], $request);
    }
}
