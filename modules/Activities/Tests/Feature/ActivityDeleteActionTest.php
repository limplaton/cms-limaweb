<?php
 

namespace Modules\Activities\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Models\User;

class ActivityDeleteActionTest extends ResourceTestCase
{
    protected $resourceName = 'activities';

    public function test_activity_delete_action()
    {
        $this->signIn();

        $activities = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $activities)->assertActionOk();
        $this->assertSoftDeleted('activities', ['id' => $activities->modelKeys()]);
    }

    public function test_unauthorized_user_cant_run_activity_delete_action()
    {
        $this->asRegularUser()->signIn();

        $activities = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $activities)->assertActionUnauthorized();
        $this->assertDatabaseHas('activities', ['id' => $activities->modelKeys()]);
    }

    public function test_authorized_user_can_run_activity_delete_action()
    {
        $this->asRegularUser()->withPermissionsTo('delete any activity')->signIn();

        $activity = $this->factory()->for(User::factory())->create();

        $this->runAction('delete-action', $activity)->assertActionOk();
        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
    }

    public function test_authorized_user_can_run_activity_delete_action_only_on_own_activities()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('delete own activities')->signIn();

        $activityForSignedIn = $this->factory()->for($signedInUser)->create();
        $otherActivity = $this->factory()->create();

        $this->runAction('delete-action', $otherActivity)->assertActionUnauthorized();
        $this->assertDatabaseHas('activities', ['id' => $otherActivity->id]);

        $this->runAction('delete-action', $activityForSignedIn);
        $this->assertSoftDeleted('activities', ['id' => $activityForSignedIn->id]);
    }

    public function test_authorized_user_can_bulk_delete_activities()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any activity', 'bulk delete activities',
        ])->signIn();

        $activities = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $activities);
        $this->assertSoftDeleted('activities', ['id' => $activities->modelKeys()]);
    }

    public function test_authorized_user_can_bulk_delete_only_own_activities()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo([
            'delete own activities',
            'bulk delete activities',
        ])->signIn();

        $activitiesForSignedInUser = $this->factory()->count(2)->for($signedInUser)->create();
        $otherActivity = $this->factory()->create();

        $this->runAction('delete-action', $activitiesForSignedInUser->push($otherActivity))->assertActionOk();
        $this->assertDatabaseHas('activities', ['id' => $otherActivity->id]);
        $this->assertSoftDeleted('activities', ['id' => $activitiesForSignedInUser->modelKeys()]);
    }

    public function test_unauthorized_user_cant_bulk_delete_activities()
    {
        $this->asRegularUser()->signIn();

        $activities = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $activities)->assertActionUnauthorized();
        $this->assertDatabaseHas('activities', ['id' => $activities->modelKeys()]);
    }

    public function test_user_without_bulk_delete_permission_cannot_bulk_delete_activities()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any activity',
            'delete own activities',
            'delete team activities',
        ])->signIn();

        $activities = $this->factory()->for(User::factory())->count(2)->create();

        $this->runAction('delete-action', $activities)->assertActionUnauthorized();
        $this->assertDatabaseHas('activities', ['id' => $activities->modelKeys()]);
    }
}
