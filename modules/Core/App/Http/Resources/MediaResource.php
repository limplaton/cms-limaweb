<?php
 

namespace Modules\Core\App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \Modules\Core\App\Models\Media */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'file_name' => $this->basename,
            'extension' => $this->extension,
            'size' => $this->size,
            'disk_path' => $this->getDiskPath(),
            'mime_type' => $this->mime_type,
            'aggregate_type' => $this->aggregate_type,

            'view_url' => $this->getViewUrl(),
            'view_path' => $this->viewPath(),

            'preview_url' => $this->getPreviewUrl(),
            'preview_path' => $this->previewPath(),

            'download_url' => $this->getDownloadUrl(),
            'download_path' => $this->downloadPath(),

            'pending_data' => $this->whenLoaded('pendingData'),

            'via_text_attribute' => $this->viaTextAttribute(),
        ], $request);
    }
}
