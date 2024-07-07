<?php

namespace Tests\Fixtures;

use Modules\Core\App\Http\Resources\JsonResource;

class LocationJsonResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'display_name' => $this->displayName(),
            'location_type' => $this->location_type,
        ];
    }
}
