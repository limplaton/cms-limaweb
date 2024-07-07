<?php
 

namespace Modules\Activities\Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Modules\Activities\App\Models\Activity;
use Modules\Activities\App\Models\ActivityType;
use Tests\TestCase;

class ActivityTypeModelTest extends TestCase
{
    public function test_activity_type_can_be_primary()
    {
        $type = ActivityType::factory()->primary()->create();

        $this->assertTrue($type->isPrimary());

        $type->flag = null;
        $type->save();

        $this->assertFalse($type->isPrimary());
    }

    public function test_activity_type_can_be_default()
    {
        $type = ActivityType::factory()->primary()->create();

        ActivityType::setDefault($type->id);

        $this->assertEquals($type->id, ActivityType::getDefaultType());
    }

    public function test_type_has_activities()
    {
        $type = ActivityType::factory()->has(Activity::factory()->count(2))->create();

        $this->assertCount(2, $type->activities);
    }

    public function test_primary_type_cannot_be_deleted()
    {
        $type = ActivityType::factory()->primary()->create();

        $this->expectExceptionMessage(__('activities::activity.type.delete_primary_warning'));

        $type->delete();
    }

    public function test_default_type_cannot_be_deleted()
    {
        $type = ActivityType::factory()->create();
        ActivityType::setDefault($type->id);
        $this->expectExceptionMessage(__('activities::activity.type.delete_is_default'));

        $type->delete();
    }

    public function test_type_with_activities_cannot_be_deleted()
    {
        $type = ActivityType::factory()->has(Activity::factory())->create();

        $this->expectExceptionMessage(__('activities::activity.type.delete_usage_warning'));

        $type->delete();
    }

    public function test_due_activities_are_properly_queried()
    {
        $dueDate = now();

        Activity::factory()->create([
            'due_date' => $dueDate->format('Y-m-d'),
            'due_time' => $dueDate->format('H:i'),
        ]);

        $dueDate->addWeek();

        Activity::factory()->create([
            'due_date' => $dueDate->format('Y-m-d'),
            'due_time' => $dueDate->format('H:i'),
        ]);

        $this->assertSame(1, Activity::overdue()->count());
    }

    public function test_activity_type_can_be_translated_with_custom_group()
    {
        $model = ActivityType::factory()->create(['name' => 'Original']);

        Lang::addLines(['custom.activity_type.'.$model->id => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_activity_type_can_be_translated_with_lang_key()
    {
        $model = ActivityType::factory()->create(['name' => 'custom.activity_type.some']);

        Lang::addLines(['custom.activity_type.some' => 'Changed'], 'en');

        $this->assertSame('Changed', $model->name);
    }

    public function test_it_uses_database_name_when_no_custom_trans_available()
    {
        $model = ActivityType::factory()->create(['name' => 'Database Name']);

        $this->assertSame('Database Name', $model->name);
    }
}
