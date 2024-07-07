<?php
 

namespace Modules\Activities\Tests\Feature;

use DateTime;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Activities\App\Console\Commands\SyncNextActivityDate;
use Modules\Activities\App\Models\Activity;
use Tests\TestCase;

class SyncNextActivityDateCommandTest extends TestCase
{
    public function test_resource_has_next_activity()
    {
        $activities = $this->factory()->create();

        foreach (SyncNextActivityDate::resourcesWithNextActivity() as $resource) {
            $model = $resource::$model::factory()->create();
            $model->activities()->attach($activities);

            $this->invokeSync();
            $model->refresh();
            $this->assertTrue($activities[0]->is($model->nextActivity));
            $this->assertEquals(new DateTime($activities[0]->fullDueDate), $model->next_activity_date);

            $model->activities()->where('id', $activities[0]->id)->detach();

            $this->invokeSync();

            $model->refresh();
            $this->assertTrue($activities[1]->is($model->nextActivity));
            $this->assertEquals(new DateTime($activities[1]->fullDueDate), $model->next_activity_date);
        }
    }

    public function test_resource_next_activity_is_cleared_when_has_no_activities()
    {
        foreach (SyncNextActivityDate::resourcesWithNextActivity() as $resource) {
            $model = $resource::$model::factory()->has($this->factory())->create();
            $this->invokeSync();

            $model->activities()->detach();
            $this->invokeSync();
            $model->refresh();

            $this->assertNull($model->nextActivity);
            $this->assertNull($model->next_activity_date);
        }
    }

    public function test_resource_next_activity_is_cleared_when_activities_are_completed()
    {
        foreach (SyncNextActivityDate::resourcesWithNextActivity() as $resource) {
            $model = $resource::$model::factory()->has($this->factory(1))->create();
            $this->invokeSync();

            $model->activities[0]->markAsComplete();

            $this->invokeSync();

            $model->refresh();
            $this->assertNull($model->nextActivity);
            $this->assertNull($model->next_activity_date);
        }
    }

    public function test_resource_record_updated_at_is_not_updated_when_next_activity_is_updated()
    {
        foreach (SyncNextActivityDate::resourcesWithNextActivity() as $resource) {
            $activities = $this->factory();

            $model = $resource::$model::factory()->has($activities)->create([
                'updated_at' => $updatedAt = now()->subDays(2),
            ]);

            $activities = $model->activities;

            $this->invokeSync();

            $model->refresh();
            $this->assertTrue($activities[0]->is($model->nextActivity));
            $this->assertSame($updatedAt->format('Y-m-d H:i:s'), $model->updated_at->format('Y-m-d H:i:s'));

            $model->activities()->where('id', $activities[0]->id)->detach();

            $this->invokeSync();

            $model->refresh();
            $this->assertSame($updatedAt->format('Y-m-d H:i:s'), $model->updated_at->format('Y-m-d H:i:s'));
        }
    }

    protected function invokeSync()
    {
        $this->artisan(SyncNextActivityDate::class);
    }

    protected function factory($count = 2)
    {
        $now = now();

        return Activity::factory()->count($count)->state(new Sequence(
            ['due_date' => $now->addWeeks(1)->format('Y-m-d'), 'due_time' => $now->format('H:i:s')],
            ['due_date' => $now->addWeeks(2)->format('Y-m-d'), 'due_time' => $now->format('H:i:s')]
        ));
    }
}
