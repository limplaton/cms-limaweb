<?php
 

namespace Modules\Deals\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\App\Support\GateHelper;

class DealBoardCardResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return with([
            'id' => $this->id,
            'name' => $this->name, // for activity create modal
            'amount' => $this->amount ?? 0,
            'display_name' => $this->displayName(),
            'path' => $this->path(),
            'status' => $this->status->name,
            'authorizations' => GateHelper::authorizations($this->resource),
            'expected_close_date' => $this->expected_close_date,
            'next_activity_date' => $this->next_activity_date,
            'incomplete_activities_for_user_count' => (int) $this->incomplete_activities_for_user_count,
            'products_count' => (int) $this->products_count,
            'user_id' => $this->user_id,
            'swatch_color' => $this->swatch_color,
            'stage_id' => $this->stage_id,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ], function ($attributes) {
            if (! is_null($this->expected_close_date)) {
                $attributes['falls_behind_expected_close_date'] = $this->fallsBehindExpectedCloseDate;
            }

            return $attributes;
        });
    }
}
