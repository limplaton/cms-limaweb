<?php
 

namespace Modules\Billable\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;

class MarkProductAsInactiveTest extends ResourceTestCase
{
    protected $action = 'mark-product-as-inactive';

    protected $resourceName = 'products';

    public function test_super_admin_user_can_run_mark_as_inactive_action()
    {
        $this->signIn();
        $product = $this->factory()->active()->create();

        $this->runAction($this->action, $product->id)->assertActionOk();
        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_authorized_user_can_run_mark_as_inactive_action()
    {
        $this->asRegularUser()->withPermissionsTo('edit all products')->signIn();

        $user = $this->createUser();
        $product = $this->factory()->active()->for($user, 'creator')->create();

        $this->runAction($this->action, $product->id)->assertActionOk();
        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_unauthorized_user_can_run_mark_as_inactive_action_on_own_deal()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own products')->signIn();

        $productForSignedIn = $this->factory()->active()->for($signedInUser, 'creator')->create();
        $otherProduct = $this->factory()->active()->create();

        $this->runAction($this->action, $otherProduct->id)->assertActionUnauthorized();
        $this->runAction($this->action, $productForSignedIn->id);
        $this->assertFalse($productForSignedIn->fresh()->is_active);
    }
}
