<?php
 

namespace Modules\Deals\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Activities\App\Http\Resources\ActivityResource;
use Modules\Billable\App\Http\Resources\BillableResource;
use Modules\Calls\App\Http\Resources\CallResource;
use Modules\Core\App\Http\Resources\ChangelogResource;
use Modules\Core\App\Http\Resources\MediaResource;
use Modules\Core\App\Resource\JsonResource;
use Modules\Deals\App\Enums\DealStatus;
use Modules\MailClient\App\Http\Resources\EmailAccountMessageResource;
use Modules\Notes\App\Http\Resources\NoteResource;

/** @mixin \Modules\Deals\App\Models\Deal */
class DealResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        ChangelogResource::topLevelResource($this->resource);

        return $this->withCommonData([
            'notes_count' => $this->whenCounted('notes', fn () => (int) $this->notes_count),
            'calls_count' => $this->whenCounted('calls', fn () => (int) $this->calls_count),
            'products_count' => $this->whenCounted('calls', fn () => (int) $this->products_count),

            $this->mergeWhen($this->status === DealStatus::lost, [
                'lost_reason' => $this->lost_reason,
                'lost_date' => $this->lost_date,
            ]),

            $this->mergeWhen($this->status === DealStatus::won, [
                'won_date' => $this->won_date,
            ]),

            'stage_changed_date' => $this->stage_changed_date,

            $this->mergeWhen(! is_null($this->expected_close_date) && ! $request->isZapier(), [
                'falls_behind_expected_close_date' => $this->fallsBehindExpectedCloseDate,
            ]),

            $this->mergeWhen(! $request->isZapier() && $this->userCanViewCurrentResource(), [
                'board_order' => $this->board_order,
                'time_in_stages' => $this->whenLoaded('stagesHistory', function () {
                    return $this->timeInStages();
                }),
                'billable' => new BillableResource($this->whenLoaded('billable')),
                'changelog' => ChangelogResource::collection($this->whenLoaded('changelog')),
                'notes' => NoteResource::collection($this->whenLoaded('notes')),
                'calls' => CallResource::collection($this->whenLoaded('calls')),
                'activities' => ActivityResource::collection($this->whenLoaded('activities')),
                'media' => MediaResource::collection($this->whenLoaded('media')),
                'emails' => EmailAccountMessageResource::collection($this->whenLoaded('emails')),
            ]),
        ], $request);
    }
}
