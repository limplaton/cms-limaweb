<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** @mixin \Modules\Core\App\Models\Changelog */
class ChangelogResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'description' => $this->description,
            'causer_name' => $this->causer_name,
            'properties' => $this->properties,
            'module' => str_starts_with($this->subject_type, config('modules.namespace')) ?
                strtolower(Str::of($this->subject_type)->explode('\\')[1]) :
                null,
        ], $request);
    }
}
