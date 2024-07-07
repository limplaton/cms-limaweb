<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\Workflow */
class WorkflowResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'total_executions' => $this->total_executions,
            'trigger_type' => $this->trigger_type,
            'action_type' => $this->action_type,
            'data' => $this->data,
        ], $request);
    }
}
