<?php
 

namespace Modules\Billable\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Resource\JsonResource;

/** @mixin \Modules\Billable\App\Models\Product */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        // TODO, in future allow the resource to not be always required and use some
        // default resource like this one
        return $this->withCommonData([], $request);
    }
}
