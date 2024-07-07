<?php
 

namespace Modules\Activities\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Models\User;

class MarkActivityAsCompleteTest extends ResourceTestCase
{
    protected $action = 'mark-activity-as-complete';

    protected $resourceName = 'activities';

    public function test_mark_activity_as_complete_action()
    {
        $this->signIn();
        $this->createUser();
        $activity = $this->factory()->create();

        $this->runAction($this->action, $activity)->assertActionOk();
        $this->assertTrue((bool) $activity->fresh()->is_completed);
    }

    public function test_authorized_user_can_run_mark_activity_as_complete_action()
    {
        $this->asRegularUser()->withPermissionsTo('edit all activities')->signIn();

        $activity = $this->factory()->has(User::factory())->create();

        $this->runAction($this->action, $activity)->assertActionOk();
        $this->assertTrue((bool) $activity->fresh()->is_completed);
    }

    public function test_unauthorized_user_can_run_mark_activity_as_complete_action_on_own_activity()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own activities')->signIn();

        $activityForSignedIn = $this->factory()->for($signedInUser)->create();
        $otherActivity = $this->factory()->create();

        $this->runAction($this->action, $otherActivity)->assertActionUnauthorized();
        $this->runAction($this->action, $activityForSignedIn)->assertActionOk();
        $this->assertTrue((bool) $activityForSignedIn->fresh()->is_completed);
    }
}
