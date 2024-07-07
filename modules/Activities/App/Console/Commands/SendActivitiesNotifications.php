<?php
 

namespace Modules\Activities\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Notifications\ActivityReminder;

class SendActivitiesNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifies owners of due activities.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Activity::reminderable()
            ->with(['user', 'type'])
            ->get()
            ->each(function (Activity $activity) {
                $activity->calendarable = false;

                try {
                    $activity->user->notify(new ActivityReminder($activity));
                } finally {
                    // To avoid infinite loops in case there are error, we will
                    // mark the activity as notified
                    $activity->forceFill(['reminded_at' => now()])->save();
                }
            });
    }
}
