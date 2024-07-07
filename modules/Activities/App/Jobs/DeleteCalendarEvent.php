<?php
 

namespace Modules\Activities\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Activities\App\Models\Calendar;

class DeleteCalendarEvent implements ShouldQueue
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
    public function __construct(public Calendar $calendar, public int $activityId, public string|int $eventId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->calendar->synchronizer()->deleteEvent($this->activityId, $this->eventId);
    }
}
