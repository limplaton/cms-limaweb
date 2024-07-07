<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\Filter */
class FilterResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'identifier' => $this->identifier,
            'rules' => $this->rules,
            'user_id' => $this->user_id,
            'is_shared' => $this->is_shared,
            'is_shared_from_another_user' => $this->isSharedFromAnotherUser($request),
            'is_system_default' => $this->is_system_default,
            'is_readonly' => $this->is_readonly,
            'defaults' => $this->defaults->map(fn ($default) => [
                'user_id' => $default->user_id,
                'view' => $default->view,
            ])->values(),
        ], $request);
    }

    protected function isSharedFromAnotherUser(Request $request): bool
    {
        if ($this->is_system_default || ! $this->is_shared) {
            return false;
        }

        return $this->user_id !== $request->user()->getKey();
    }
}
