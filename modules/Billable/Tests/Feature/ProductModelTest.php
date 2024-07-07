<?php
 

namespace Modules\Billable\Tests\Feature;

use Modules\Billable\App\Models\BillableProduct;
use Modules\Billable\App\Models\Product;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    public function test_when_product_created_by_not_provided_uses_current_user_id()
    {
        $user = $this->signIn();

        $product = Product::factory(['created_by' => null])->create();

        $this->assertEquals($product->created_by, $user->id);
    }

    public function test_product_created_by_can_be_provided()
    {
        $user = $this->createUser();

        $product = Product::factory()->for($user, 'creator')->create();

        $this->assertEquals($product->created_by, $user->id);
    }

    public function test_product_has_path()
    {
        $product = Product::factory()->create();

        $this->assertEquals('/products/'.$product->id, $product->path());
    }

    public function test_product_has_display_name_attribute()
    {
        $product = Product::factory(['name' => 'Product name'])->make();

        $this->assertEquals('Product name', $product->displayName());
    }

    public function test_product_has_billable_products()
    {
        $product = Product::factory()->has(BillableProduct::factory()->count(2), 'billables')->create();

        $this->assertCount(2, $product->billables);
    }
}
