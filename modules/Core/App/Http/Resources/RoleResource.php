<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\Role */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ], $request);
    }
}
