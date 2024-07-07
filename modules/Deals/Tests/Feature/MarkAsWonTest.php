<?php
 

namespace Modules\Deals\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\App\Enums\DealStatus;
use Modules\Users\App\Models\User;

class MarkAsWonTest extends ResourceTestCase
{
    protected $action = 'mark-as-won';

    protected $resourceName = 'deals';

    public function test_super_admin_user_can_run_deal_mark_as_won_action()
    {
        $this->signIn();
        $deal = $this->factory()->create();

        $this->runAction($this->action, $deal)->assertActionOk();
        $this->assertSame(DealStatus::won, $deal->fresh()->status);
    }

    public function test_deal_mark_as_won_action_throws_confetti()
    {
        $this->signIn();
        $deal = $this->factory()->create();

        $this->runAction($this->action, $deal)->assertExactJson(['confetti' => true]);
    }

    public function test_authorized_user_can_run_deal_mark_as_won_action()
    {
        $this->asRegularUser()->withPermissionsTo('edit all deals')->signIn();

        $deal = $this->factory()->for(User::factory())->create();

        $this->runAction($this->action, $deal)->assertActionOk();
        $this->assertSame(DealStatus::won, $deal->fresh()->status);
    }

    public function test_unauthorized_user_can_run_deal_mark_as_won_action_on_own_deal()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own deals')->signIn();
        $this->createUser();

        $dealForSignedIn = $this->factory()->for($signedInUser)->create();
        $otherDeal = $this->factory()->create();

        $this->runAction($this->action, $otherDeal)->assertActionUnauthorized();
        $this->runAction($this->action, $dealForSignedIn);
        $this->assertSame(DealStatus::won, $dealForSignedIn->fresh()->status);
    }
}
