<?php
 

namespace Modules\Contacts\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Resource\JsonResource;

/** @mixin \Modules\Contacts\App\Models\Industry */
class IndustryResource extends JsonResource
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
        ], $request);
    }
}
