<?php
 

namespace Modules\Users\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Http\Resources\JsonResource;

/** @mixin \Modules\Users\App\Models\Team */
class TeamResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            $this->mergeWhen($request->user()->isSuperAdmin(), [
                'description' => $this->description,
            ]),
            'user_id' => $this->user_id,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'members' => UserResource::collection($this->whenLoaded('users')),
        ], $request);
    }
}
