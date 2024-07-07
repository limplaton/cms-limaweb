<?php

namespace Tests\Fixtures;

use Modules\Core\App\Resource\JsonResource;

class CalendarJsonResource extends JsonResource
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
            'title' => $this->title,
        ];
    }
}
