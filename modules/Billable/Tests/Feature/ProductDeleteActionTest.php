<?php
 

namespace Modules\Billable\Tests\Feature;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\App\Models\User;

/**
 * Contains tests for update as well, but not used currently.
 */
class ProductDeleteActionTest extends ResourceTestCase
{
    protected $resourceName = 'products';

    public function test_product_delete_action()
    {
        $this->signIn();

        $products = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $products)->assertActionOk();
        $this->assertSoftDeleted('products', ['id' => $products->modelKeys()]);
    }

    public function test_unauthorized_user_cant_run_product_delete_action()
    {
        $this->asRegularUser()->signIn();

        $products = $this->factory()->for(User::factory(), 'creator')->count(2)->create();

        $this->runAction('delete-action', $products)->assertActionUnauthorized();
        $this->assertDatabaseHas('products', ['id' => $products->modelKeys()]);
    }

    public function test_authorized_user_can_run_product_delete_action()
    {
        $this->asRegularUser()->withPermissionsTo('delete any product')->signIn();

        $product = $this->factory()->for(User::factory(), 'creator')->create();

        $this->runAction('delete-action', $product)->assertActionOk();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_authorized_user_can_run_product_delete_action_only_on_own_products()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo('delete own products')->signIn();

        $productForSignedIn = $this->factory()->for($signedInUser, 'creator')->create();
        $otherproduct = $this->factory()->create();

        $this->runAction('delete-action', $otherproduct)->assertActionUnauthorized();
        $this->assertDatabaseHas('products', ['id' => $otherproduct->id]);

        $this->runAction('delete-action', $productForSignedIn);
        $this->assertSoftDeleted('products', ['id' => $productForSignedIn->id]);
    }

    public function test_authorized_user_can_bulk_delete_products()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any product', 'bulk delete products',
        ])->signIn();

        $products = $this->factory()->for(User::factory(), 'creator')->count(2)->create();

        $this->runAction('delete-action', $products);
        $this->assertSoftDeleted('products', ['id' => $products->modelKeys()]);
    }

    public function test_authorized_user_can_bulk_delete_only_own_products()
    {
        $signedInUser = $this->asRegularUser()->withPermissionsTo([
            'delete own products',
            'bulk delete products',
        ])->signIn();

        $productsForSignedInUser = $this->factory()->count(2)->for($signedInUser, 'creator')->create();
        $otherproduct = $this->factory()->create();

        $this->runAction('delete-action', $productsForSignedInUser->push($otherproduct))->assertActionOk();
        $this->assertDatabaseHas('products', ['id' => $otherproduct->id]);
        $this->assertSoftDeleted('products', ['id' => $productsForSignedInUser->modelKeys()]);
    }

    public function test_unauthorized_user_cant_bulk_delete_products()
    {
        $this->asRegularUser()->signIn();

        $products = $this->factory()->count(2)->create();

        $this->runAction('delete-action', $products)->assertActionUnauthorized();
        $this->assertDatabaseHas('products', ['id' => $products->modelKeys()]);
    }

    public function test_user_without_bulk_delete_permission_cannot_bulk_delete_products()
    {
        $this->asRegularUser()->withPermissionsTo([
            'delete any product',
            'delete own products',
            'delete team products',
        ])->signIn();

        $products = $this->factory()->for(User::factory(), 'creator')->count(2)->create();

        $this->runAction('delete-action', $products)->assertActionUnauthorized();
        $this->assertDatabaseHas('products', ['id' => $products->modelKeys()]);
    }
}
