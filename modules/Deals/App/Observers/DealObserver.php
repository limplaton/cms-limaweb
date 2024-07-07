<?php
 

namespace Modules\Deals\App\Observers;

use Modules\Core\App\Models\PinnedTimelineSubject;
use Modules\Deals\App\Enums\DealStatus;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Stage;

class DealObserver
{
    /**
     * Handle the Deal "saving" event.
     */
    public function saving(Deal $deal): void
    {
        if ($deal->isDirty('status')) {
            if ($deal->isOpen()) {
                $deal->fill(['won_date' => null, 'lost_date' => null, 'lost_reason' => null]);
            } elseif ($deal->isLost()) {
                $deal->fill(['lost_date' => now(), 'won_date' => null]);
            } elseif ($deal->isWon()) {
                $deal->fill(['won_date' => now(), 'lost_date' => null, 'lost_reason' => null]);
            }
        }

        if ($deal->isDirty(['pipeline_id', 'stage_id'])) {
            $deal->pipeline_id = Stage::findFromObjectCache($deal->stage_id)->getAttribute('pipeline_id');
        }
    }

    /**
     * Handle the Deal "creating" event.
     */
    public function creating(Deal $deal): void
    {
        if (! $deal->status) {
            $deal->status = DealStatus::open;
        }
    }

    /**
     * Handle the Deal "created" event.
     */
    public function created(Deal $deal): void
    {
        if ($deal->isOpen()) {
            $deal->startStageHistory();
        }

        // When new deal is created, always increment the board order so the new
        // deal is pushed on top and all deals are ordered properly
        if ($deal->board_order === 0) { // via tests?
            Deal::withoutEvents(fn () => $deal->newQuery()->increment('board_order'));
        }
    }

    /**
     * Handle the Deal "updating" event.
     */
    public function updating(Deal $deal): void
    {
        if ($deal->isDirty('stage_id')) {
            $deal->stage_changed_date = now();
        }

        if (! $deal->isDirty('status')) {
            // Guard these attributes when the status is not changed
            foreach (['won_date', 'lost_date'] as $guarded) {
                if ($deal->isDirty($guarded)) {
                    $deal->fill([$guarded => $deal->getOriginal($guarded)]);
                }
            }

            // Allow updating the lost reason only when status is lost
            if (! $deal->isLost() && $deal->isDirty('lost_reason')) {
                $deal->fill(['lost_reason' => $deal->getOriginal('lost_reason')]);
            }
        }
    }

    /**
     * Handle the Deal "updated" event.
     */
    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('status')) {
            $changelog = $deal->logStatusChangeActivity(
                'marked_as_'.$deal->status->name,
                $deal->isLost() ? [
                    'reason' => $deal->lost_reason,
                ] : []
            );

            if ($changelog && $deal->isLost()) {
                (new PinnedTimelineSubject)->pin(
                    $deal->id, $deal::class, $changelog->getKey(), $changelog::class
                );
            }

            if ($deal->isOpen()) {
                $deal->startStageHistory();
            } else {
                // Changed to won or lost
                $deal->stopLastStageTiming();
            }
        }

        if ($deal->wasChanged('stage_id') && $deal->isOpen()) {
            $deal->recordStageHistory($deal->stage_id);
        }
    }

    /**
     * Handle the Deal "deleting" event.
     */
    public function deleting(Deal $deal): void
    {
        if ($deal->isForceDeleting()) {
            $deal->purge();
        }
    }
}
