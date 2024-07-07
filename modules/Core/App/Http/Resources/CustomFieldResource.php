<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\CustomField */
class CustomFieldResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'field_type' => $this->field_type,
            'resource_name' => $this->resource_name,
            'field_id' => $this->field_id,
            'label' => $this->label,
            'options' => $this->when($this->options->isNotEmpty(), $this->options),
            'is_unique' => $this->is_unique,
        ], $request);
    }
}
