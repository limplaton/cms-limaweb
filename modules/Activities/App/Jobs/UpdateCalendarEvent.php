<?php
 

namespace Modules\Activities\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\Calendar;

class UpdateCalendarEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public Calendar $calendar, public Activity $activity, public string|int $eventId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->activity->loadMissing('guests.guestable');

        $this->calendar->synchronizer()->updateEvent($this->activity, $this->eventId);
    }
}
