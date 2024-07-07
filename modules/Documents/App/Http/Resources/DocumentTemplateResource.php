<?php
 

namespace Modules\Documents\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Resource\JsonResource;

/** @mixin \Modules\Documents\App\Models\DocumentTemplate */
class DocumentTemplateResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'content' => clean($this->content),
            'view_type' => $this->view_type,
            'is_shared' => $this->is_shared,
            'user_id' => $this->user_id,
            'google_fonts' => $this->usedGoogleFonts(),
        ];
    }
}
