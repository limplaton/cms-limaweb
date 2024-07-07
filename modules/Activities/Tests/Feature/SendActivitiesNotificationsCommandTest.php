<?php
 

namespace Modules\Activities\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Notifications\ActivityReminder;
use Tests\TestCase;

class SendActivitiesNotificationsCommandTest extends TestCase
{
    public function test_activities_notifications_command()
    {
        Notification::fake();

        $activity = Activity::factory()->create([
            'due_date' => date('Y-m-d', strtotime('+29 minutes')),
            'due_time' => date('H:i:s', strtotime('+29 minutes')),
            'reminder_minutes_before' => 30,
        ]);

        $this->artisan('activities:notify')->assertSuccessful();

        Notification::assertSentTo($activity->user, ActivityReminder::class);
        Notification::assertSentToTimes($activity->user, ActivityReminder::class, 1);
        $this->assertNotNull($activity->fresh()->reminded_at);
    }
}
