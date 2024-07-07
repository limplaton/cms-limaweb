<?php
 

namespace Modules\Activities\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Resource\JsonResource;

/** @mixin \Modules\Activities\App\Models\ActivityType */
class ActivityTypeResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'flag' => $this->flag,
            'swatch_color' => $this->swatch_color,
        ], $request);
    }
}
