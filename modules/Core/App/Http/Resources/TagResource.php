<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\Tag */
class TagResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'swatch_color' => $this->swatch_color,
            'display_order' => $this->display_order,
            'type' => $this->type,
        ];
    }
}
