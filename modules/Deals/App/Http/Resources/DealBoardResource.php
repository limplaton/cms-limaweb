<?php
 

namespace Modules\Deals\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Modules\Deals\App\Models\Stage */
class DealBoardResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'win_probability' => $this->win_probability,
            'display_order' => $this->display_order,
            'summary' => $this->calculated_summary,
            'cards' => DealBoardCardResource::collection($this->deals),
        ];
    }
}
