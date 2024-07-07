<?php
 

namespace Modules\Deals\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Resource\JsonResource;

/** @mixin \Modules\Deals\App\Models\Pipeline */
class PipelineResource extends JsonResource
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
            $this->mergeWhen($this->relationLoaded('userOrder'), function () {
                return [
                    'user_display_order' => $this->userOrder?->display_order,
                ];
            }),
            $this->mergeWhen(! $request->isZapier(), [
                'visibility_group' => $this->visibilityGroupData(),
                'flag' => $this->flag,
                'stages' => StageResource::collection($this->whenLoaded('stages')),
            ]),
        ], $request);
    }
}
