<?php
 

namespace Modules\Activities\App\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Modules\Activities\App\Jobs\CreateCalendarEvent;
use Modules\Activities\App\Jobs\UpdateCalendarEvent;
use Modules\Activities\App\Models\Activity;

class ActivityTransactionAwareObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        if ($activity->canSyncToCalendar()) {
            CreateCalendarEvent::dispatch($activity->user->calendar, $activity);
        }
    }

    /**
     * Handle the Activity "updated" event.
     */
    public function updated(Activity $activity): void
    {
        if ($activity->calendarable) {
            if ($activity->user_id !== $activity::$preUpdateUser->getKey()) {
                // Perhaps check if import is running?
                $activity->deleteFromCalendar($activity::$preUpdateUser);

                if ($activity->canSyncToCalendar()) {
                    CreateCalendarEvent::dispatch($activity->user->calendar, $activity);
                }
            } elseif (! $activity->wasChanged($activity->getDeletedAtColumn()) && // triggers create via "restored"
                $activity->isSynchronizedToCalendar($activity->user->calendar) &&
                $activity->canSyncToCalendar()) {
                UpdateCalendarEvent::dispatch(
                    $activity->user->calendar,
                    $activity,
                    $activity->latestSynchronization()->pivot->event_id
                );
            }
        }

        $activity::$preUpdateUser = null;
    }

    /**
     * Handle the Activity "restored" event.
     */
    public function restored(Activity $activity): void
    {
        if ($activity->canSyncToCalendar()) {
            CreateCalendarEvent::dispatch($activity->user->calendar, $activity);
        }
    }

    /**
     * Handle the Activity "deleting" event.
     */
    public function deleting(Activity $activity): void
    {
        if ($activity->calendarable) {
            $activity->load(['user', 'synchronizations'])->deleteFromCalendar();
        }
    }
}
