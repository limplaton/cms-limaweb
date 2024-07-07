<?php
 

namespace Modules\Brands\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Modules\Brands\App\Models\Brand */
class BrandResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'is_default' => $this->is_default,
            'config' => $this->config,
            'logo_view' => $this->logo_view,
            'logo_view_url' => $this->logoViewUrl,
            'logo_mail' => $this->logo_mail,
            'logo_mail_url' => $this->logoMailUrl,
            $this->mergeWhen(! $request->isZapier(), [
                'visibility_group' => $this->visibilityGroupData(),
            ]),
        ];
    }
}
