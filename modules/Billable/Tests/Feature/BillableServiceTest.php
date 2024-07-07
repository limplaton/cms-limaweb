<?php
 

namespace Modules\Billable\Tests\Feature;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Billable\App\Enums\TaxType;
use Modules\Billable\App\Models\Billable;
use Modules\Billable\App\Models\BillableProduct;
use Modules\Billable\App\Models\Product;
use Modules\Billable\App\Services\BillableService;
use Modules\Core\Database\Seeders\SettingsSeeder;
use Modules\Deals\App\Models\Deal;
use Tests\TestCase;

class BillableServiceTest extends TestCase
{
    public function test_it_creates_new_billable_when_not_exists()
    {
        $billableable = Deal::factory()->create();
        $billable = (new BillableService())->save(['tax_type' => TaxType::no_tax], $billableable);

        $this->assertTrue($billable->wasRecentlyCreated);
    }

    public function test_it_uses_default_tax_type_when_billable_is_created_and_no_tax_type_is_provided()
    {
        $this->seed(SettingsSeeder::class);
        Billable::setDefaultTaxType(TaxType::inclusive);
        $service = new BillableService();

        $billableable = Deal::factory()->create();

        $billable = $service->save([], $billableable);
        $this->assertEquals(TaxType::inclusive, $billable->tax_type);

        $billable = $service->save(['tax_type' => null], $billableable);
        $this->assertEquals(TaxType::inclusive, $billable->tax_type);

        $billable = $service->save(['tax_type' => ''], $billableable);
        $this->assertEquals(TaxType::inclusive, $billable->tax_type);
    }

    public function test_it_uses_the_name_from_the_selected_product_when_name_is_not_provided()
    {
        $billable = Billable::factory()->withBillableable()->create();
        $product = Product::factory()->create();

        $billable = (new BillableService())->save(['products' => [[
            'billable_id' => $billable->id,
            // 'name'        => 'Name',
            'description' => 'Product description',
            'unit_price' => 5000,
            'qty' => 2,
            'tax_label' => 'TAX',
            'tax_rate' => 12,
            'product_id' => $product->id,
        ]]], $billable->billableable);

        $this->assertSame($product->name, $billable->products[0]->name);
    }

    public function test_it_does_not_clear_the_tax_type_when_billable_exists_and_tax_type_is_not_provided()
    {
        $service = new BillableService();
        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        $billable = $service->save(['tax_type' => ''], $billable->billableable);
        $this->assertEquals(TaxType::exclusive, $billable->tax_type);

        $billable = $service->save(['tax_type' => null], $billable->billableable);
        $this->assertEquals(TaxType::exclusive, $billable->tax_type);

        $billable = $service->save([], $billable->billableable);
        $this->assertEquals(TaxType::exclusive, $billable->tax_type);
    }

    public function test_billable_tax_type_can_be_set()
    {
        $service = new BillableService();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        // Existing
        $billable = $service->save(['tax_type' => TaxType::inclusive], $billable->billableable);
        $this->assertEquals(TaxType::inclusive, $billable->tax_type);

        // New
        $billableable = Deal::factory()->create();
        $billable = $service->save(['tax_type' => TaxType::inclusive], $billableable);

        $this->assertEquals(TaxType::inclusive, $billable->tax_type);
    }

    public function test_it_does_not_create_billable_when_exists()
    {
        $service = new BillableService();
        $billable = Billable::factory()->withBillableable()->create();

        $billable = $service->save([], $billable->billableable);

        $this->assertFalse($billable->wasRecentlyCreated);
    }

    public function test_billable_has_products()
    {
        $service = new BillableService();

        $billable = Billable::factory()->taxExclusive()->withBillableable()->create();

        $product = [
            'billable_id' => $billable->id,
            'name' => 'Product name',
            'description' => 'Product description',
            'unit_price' => 5000,
            'qty' => 2,
            'tax_label' => 'TAX',
            'tax_rate' => 12,
            'product_id' => Product::factory()->create()->id,
            'discount_type' => 'percent',
            'discount_total' => 20,
        ];

        $billable = $service->save(['products' => [$product]], $billable->billableable);

        $this->assertCount(1, $billable->products);
        $this->assertEquals($product['product_id'], $billable->products[0]->originalProduct->id);
        $this->assertEquals('Product name', $billable->products[0]->name);
        $this->assertEquals('Product description', $billable->products[0]->description);
        $this->assertEquals(5000, $billable->products[0]->unit_price);
        $this->assertEquals(2, $billable->products[0]->qty);
        $this->assertEquals('TAX', $billable->products[0]->tax_label);
        $this->assertEquals(12, $billable->products[0]->tax_rate);
        $this->assertEquals('percent', $billable->products[0]->discount_type);
        $this->assertEquals(20, $billable->products[0]->discount_total);
    }

    public function test_billable_product_can_be_updated()
    {
        $service = new BillableService();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->has(BillableProduct::factory(), 'products')
            ->create();

        $product = [
            'product_id' => $billable->products[0]->originalProduct->id,
            'id' => $billable->products[0]->id,
            'name' => $billable->products[0]->name,
            'description' => 'New Product Description',
            'unit_price' => 8500,
            'qty' => 2,
            'display_order' => 2,
            'tax_rate' => 15,
            'tax_label' => 'NEW-TAX',
            'discount_type' => 'fixed',
            'discount_total' => 250,
        ];

        $service->save(['products' => [$product]], $billable->billableable);

        $billable->refresh();

        $this->assertEquals($product['name'], $billable->products[0]->name);
        $this->assertEquals($product['description'], $billable->products[0]->description);
        $this->assertEquals($product['unit_price'], $billable->products[0]->unit_price);
        $this->assertEquals($product['qty'], $billable->products[0]->qty);
        $this->assertEquals($product['display_order'], $billable->products[0]->display_order);
        $this->assertEquals($product['tax_rate'], $billable->products[0]->tax_rate);
        $this->assertEquals($product['tax_label'], $billable->products[0]->tax_label);
        $this->assertEquals($product['discount_type'], $billable->products[0]->discount_type);
        $this->assertEquals($product['discount_total'], $billable->products[0]->discount_total);
    }

    public function test_it_does_not_create_new_product_when_product_id_is_provided()
    {
        $service = new BillableService();

        $billable = Billable::factory()->withBillableable()->create();

        $product = [
            'billable_id' => $billable->id,
            'name' => 'Product name',
            'unit_price' => 5000,
            'qty' => 1,
            'tax_label' => 'TAX',
            'tax_rate' => 0,
            'product_id' => Product::factory()->create()->id,
        ];

        $billable = $service->save(['products' => [$product]], $billable->billableable);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_a_new_product_is_created_when_product_id_is_not_provided()
    {
        $service = new BillableService();

        $this->signIn();

        BillableProduct::factory()->make(['name' => 'New Product', 'product_id' => null])->toArray();
        $billable = Billable::factory()->withBillableable()->create();

        $service->save(['products' => [[
            'name' => 'New Product',
            'description' => 'New Product Description',
            'unit_price' => 8500,
            'qty' => 2,
            'tax_rate' => 15,
            'tax_label' => 'NEW-TAX',
            'discount_type' => 'fixed',
            'discount_total' => 250,
        ]]], $billable->billableable);

        $this->assertDatabaseHas('billable_products', ['name' => 'New Product']);
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'description' => 'New Product Description',
            'unit_price' => 8500,
            'tax_rate' => 15,
            'tax_label' => 'NEW-TAX',
        ]);
        $this->assertCount(1, $billable->products);
    }

    public function test_a_new_product_is_created_when_billable_product_name_has_changed()
    {
        $service = new BillableService();

        $this->signIn();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->has(BillableProduct::factory(), 'products')
            ->create();

        $service->save(['products' => [[
            'product_id' => $billable->products[0]->originalProduct->id,
            'id' => $billable->products[0]->id,
            'name' => 'Changed name',
            'description' => 'New Product Description',
            'unit_price' => 8500,
            'qty' => 2,
            'tax_rate' => 15,
            'tax_label' => 'NEW-TAX',
            'discount_type' => 'fixed',
            'discount_total' => 250,
        ]]], $billable->billableable);

        $newProduct = Product::where('name', 'Changed name')->first();

        $this->assertDatabaseCount('products', 2);

        $this->assertDatabaseHas('products', [
            'name' => 'Changed name',
            'description' => 'New Product Description',
            'unit_price' => 8500,
            'tax_rate' => 15,
            'tax_label' => 'NEW-TAX',
        ]);

        // Ensures that the product_id is used from the new product
        $this->assertDatabaseHas('billable_products', [
            'name' => 'Changed name',
            'product_id' => $newProduct->id,
        ]);

        $this->assertCount(1, $billable->products);
    }

    public function test_it_does_not_create_new_product_if_the_billable_product_name_hasnt_changed()
    {
        $service = new BillableService();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->has(BillableProduct::factory(), 'products')
            ->create();

        $service->save(['products' => [[
            'product_id' => $billable->products[0]->originalProduct->id,
            'id' => $billable->products[0]->id,
            'name' => $billable->products[0]->name,
            'unit_price' => 4500,
            'qty' => 1,
            'tax_label' => 'TAX',
            'tax_rate' => 10,
        ]]], $billable->billableable);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_it_uses_the_existing_product_when_no_product_id_is_provided_but_product_exists_by_name()
    {
        $service = new BillableService();

        $this->signIn();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        $product = Product::factory()->create(['name' => 'Existing Product']);

        $service->save(['products' => [[
            'name' => 'Existing Product',
            'unit_price' => 1000,
            'qty' => 1,
            'tax_label' => 'TAX',
            'tax_rate' => 10,
        ]]], $billable->billableable);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('billable_products', ['product_id' => $product->id]);
    }

    public function test_it_uses_default_tax_rate_when_product_tax_rate_is_not_provided()
    {
        $service = new BillableService();

        $this->signIn();

        BillableProduct::setDefaultTaxRate(18);

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        $service->save(['products' => [[
            'name' => 'MacBook Pro',
            'unit_price' => 1000,
            'qty' => 1,
            'tax_label' => 'TAX',
        ]]], $billable->billableable);

        $this->assertEquals(18, $billable->products[0]->tax_rate);
    }

    public function test_it_uses_default_tax_label_when_product_tax_label_is_not_provided()
    {
        $service = new BillableService();

        $this->signIn();

        BillableProduct::setDefaultTaxLabel('TEST-TAX');

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        $service->save(['products' => [[
            'name' => 'MacBook Pro',
            'description' => 'Product Description',
            'unit_price' => 1000,
            'qty' => 1,
            'tax_rate' => 10,
        ]]], $billable->billableable);

        $this->assertEquals('TEST-TAX', $billable->products[0]->tax_label);
    }

    public function test_it_uses_default_discount_type_when_product_discount_type_is_not_provided()
    {
        $service = new BillableService();

        $this->signIn();

        BillableProduct::setDefaultDiscountType('fixed');

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        $service->save(['products' => [[
            'name' => 'Existing Product',
            'description' => 'Product Description',
            'unit_price' => '1000',
            'qty' => '2',
            'tax_rate' => 12,
            'tax_label' => 'TAX',
        ]]], $billable->billableable);

        $this->assertEquals('fixed', $billable->products[0]->discount_type);
    }

    public function test_it_deletes_the_provided_products_to_remove()
    {
        $service = new BillableService();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->has(BillableProduct::factory()->count(2), 'products')
            ->create();

        $billable = $service->save([
            'removed_products' => [$billable->products[0]->id],
        ], $billable->billableable);

        $this->assertCount(1, $billable->products);
    }

    public function test_it_updates_the_total_billableable_column()
    {
        $service = new BillableService();

        $billable = Billable::factory()->withBillableable()
            ->noTax()
            ->has(BillableProduct::factory(), 'products')
            ->create();

        $product = [
            'product_id' => $billable->products[0]->originalProduct->id,
            'id' => $billable->products[0]->id,
            'name' => $billable->products[0]->name,
            'description' => $billable->products[0]->description,
            'unit_price' => 8500,
            'qty' => 2,
            'tax_rate' => 0,
            'tax_label' => 'TAX',
        ];

        $billable = $service->save(['products' => [$product]], $billable->billableable);

        $totalColumn = $billable->billableable->totalColumn();
        $this->assertEquals(17000, $billable->billableable->{$totalColumn});
    }

    public function test_product_display_order_uses_index_when_not_provided()
    {
        $service = new BillableService();

        $products = BillableProduct::factory()->count(2)->state(new Sequence(
            ['display_order' => 1, ''],
            ['display_order' => null],
        ))->make()->toArray();

        $billable = Billable::factory()->withBillableable()->create();

        $service->save(['products' => $products], $billable->billableable);
        $this->assertEquals(1, $billable->products[0]->display_order);
        $this->assertEquals(2, $billable->products[1]->display_order);
    }

    public function test_when_new_product_is_provided_but_product_with_the_same_name_exists_in_trash_it_uses_the_trashed_product_as_well_restores_the_product_from_trash()
    {
        $service = new BillableService();

        $this->signIn();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        $product = Product::factory()->create(['name' => 'Existing Product']);
        $product->delete();

        $service->save(['products' => [[
            'name' => 'Existing Product',
            'unit_price' => 1000,
            'qty' => 1,
            'tax_label' => 'TAX',
            'tax_rate' => 10,
        ]]], $billable->billableable);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['deleted_at' => null, 'id' => $product->id]);
        $this->assertDatabaseHas('billable_products', ['product_id' => $product->id]);
    }
}
