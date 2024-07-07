<?php
 

namespace Modules\Activities\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Activities\App\Models\Calendar;
use Modules\Core\App\Http\Resources\OAuthAccountResource;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\Activities\App\Models\Calendar */
class CalendarResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'calendar_id' => $this->calendar_id,
            'activity_type_id' => $this->activity_type_id,
            'activity_types' => $this->activity_types,
            'start_sync_from' => $this->startSyncFrom(),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'account' => new OAuthAccountResource($this->whenLoaded('oAuthAccount')),
            'last_sync_at' => $this->synchronization->last_synchronized_at,
            'is_readonly' => $this->is_readonly,
            'is_shared' => $this->is_shared,
            'is_sync_disabled' => $this->synchronization->isSyncDisabled(),
            'is_sync_stopped' => $this->synchronization->isSyncStopped(),
            'is_synchronizing_via_webhook' => $this->isSynchronizingViaWebhook(),
            'sync_state_comment' => $this->synchronization->sync_state_comment,
            'previously_used' => Calendar::with('synchronization')
                ->where('id', '!=', $this->id)
                ->where('user_id', $this->user_id)
                ->get()
                ->map(function ($calendar) {
                    return [
                        'id' => $calendar->id,
                        'calendar_id' => $calendar->calendar_id,
                        'start_sync_from' => $calendar->synchronization->start_sync_from,
                        'created_at' => $calendar->synchronization->created_at,
                    ];
                })->sortBy('created_at'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
