<?php
 

namespace Modules\Deals\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Resource\JsonResource;

/** @mixin \Modules\Deals\App\Models\Stage */
class StageResource extends JsonResource
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
            'win_probability' => $this->win_probability,
            'display_order' => $this->display_order,
            'pipeline_id' => $this->pipeline_id,
        ], $request);
    }
}
