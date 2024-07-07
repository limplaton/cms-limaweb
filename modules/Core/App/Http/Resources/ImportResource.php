<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\Core\App\Models\Import */
class ImportResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'file_name' => $this->file_name,
            'skip_file_filename' => $this->skip_file_filename,
            'mappings' => $this->data['mappings'],
            'resource_name' => $this->resource_name,
            'status' => $this->status,
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'duplicates' => $this->duplicates,
            'progress' => $this->progress(),
            'fields' => $this->serializeFields(),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'next_batch' => $this->nextBatch(),
            'completed_at' => $this->completed_at,
            'revertable' => $this->isRevertable(),
        ], $request);
    }
}
