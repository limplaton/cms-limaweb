<?php
 

namespace Modules\Billable\Tests\Feature;

use Modules\Billable\App\Models\Billable;
use Modules\Billable\App\Models\BillableProduct;
use Modules\Billable\App\Models\Product;
use Tests\TestCase;

class BillableProductModelTest extends TestCase
{
    public function test_billable_product_amount_calculation_is_performed_on_create()
    {
        $product = $this->createProductWithPrice();

        $this->assertGreaterThan(0, $product->amount_tax_exl);
    }

    public function test_billable_product_amount_calculation_is_performed_on_update()
    {
        $product = $this->createProductWithPrice();
        $originalAmont = $product->amount_tax_exl;

        $product->qty = 5;
        $product->save();

        $this->assertNotEquals($originalAmont, $product->amount_tax_exl);
    }

    public function test_billable_product_has_original_product()
    {
        $originalProduct = Product::factory();
        $product = BillableProduct::factory()->for($originalProduct, 'originalProduct')->create();
        $this->assertInstanceOf(Product::class, $product->originalProduct);
    }

    public function test_billable_product_tax_rate_is_always_zero_when_billable_has_no_tax()
    {
        $product = $this->createProductWithPrice('no_tax', ['tax_rate' => 10]);

        $this->assertEquals(0, $product->tax_rate);
    }

    public function test_billable_product_has_sku()
    {
        $originalProduct = Product::factory(['sku' => 'SKU:123']);
        $product = BillableProduct::factory()->for($originalProduct, 'originalProduct')->create();
        $this->assertEquals('SKU:123', $product->sku);
    }

    public function test_billable_product_has_no_sku_when_the_original_product_is_deleted()
    {
        $originalProduct = Product::factory(['sku' => 'SKU:123']);
        $product = BillableProduct::factory()->for($originalProduct, 'originalProduct')->create();
        $product->originalProduct()->delete();
        $this->assertEmpty($product->sku);
    }

    public function test_billable_product_amounts_without_tax_billable()
    {
        $product = $this->createProductWithPrice('no_tax');

        $this->assertEquals(4000, $product->amount_tax_exl);
        $this->assertEquals(4000, $product->calculateAmount());
        $this->assertEquals(0, $product->discountedAmount()->getValue());
        $this->assertEquals(0, $product->totalTax()->getValue());
        $this->assertEquals(4000, $product->calculateAmountBeforeTax());

        $product = $this->createProductWithPrice('no_tax', ['discount_type' => 'fixed', 'discount_total' => 200]);

        $this->assertEquals(3800, $product->amount_tax_exl);
        $this->assertEquals(3800, $product->calculateAmount());
        $this->assertEquals(200, $product->discountedAmount()->getValue());
        $this->assertEquals(0, $product->totalTax()->getValue());
        $this->assertEquals(3800, $product->calculateAmountBeforeTax());

        $product = $this->createProductWithPrice('no_tax', ['discount_type' => 'percent', 'discount_total' => 10]);

        $this->assertEquals(3600, $product->amount_tax_exl);
        $this->assertEquals(3600, $product->calculateAmount());
        $this->assertEquals(400, $product->discountedAmount()->getValue());
        $this->assertEquals(0, $product->totalTax()->getValue());
        $this->assertEquals(3600, $product->calculateAmountBeforeTax());
    }

    public function test_billable_product_amounts_with_exclusive_tax_billable()
    {
        $product = $this->createProductWithPrice('exclusive', ['tax_rate' => 10]);

        $this->assertEquals(4000, $product->amount_tax_exl);
        $this->assertEquals(4000, $product->calculateAmount());
        $this->assertEquals(0, $product->discountedAmount()->getValue());
        $this->assertEquals(400, $product->totalTax()->getValue());
        $this->assertEquals(4000, $product->calculateAmountBeforeTax());

        $product = $this->createProductWithPrice('exclusive', ['tax_rate' => 10, 'discount_type' => 'fixed', 'discount_total' => 200]);

        $this->assertEquals(3800, $product->amount_tax_exl);
        $this->assertEquals(3800, $product->calculateAmount());
        $this->assertEquals(200, $product->discountedAmount()->getValue());
        $this->assertEquals(380, $product->totalTax()->getValue());
        $this->assertEquals(3800, $product->calculateAmountBeforeTax());

        $product = $this->createProductWithPrice('exclusive', ['tax_rate' => 10, 'discount_type' => 'percent', 'discount_total' => 10]);

        $this->assertEquals(3600, $product->amount_tax_exl);
        $this->assertEquals(3600, $product->calculateAmount());
        $this->assertEquals(400, $product->discountedAmount()->getValue());
        $this->assertEquals(360, $product->totalTax()->getValue());
        $this->assertEquals(3600, $product->calculateAmountBeforeTax());
    }

    public function test_billable_product_amounts_with_inclusive_tax_billable()
    {
        $product = $this->createProductWithPrice('inclusive', ['tax_rate' => 10]);

        $this->assertEquals(3636.364, $product->amount_tax_exl);
        $this->assertEquals(4000, $product->calculateAmount());
        $this->assertEquals(0, $product->discountedAmount()->getValue());
        $this->assertEquals(363.64, $product->totalTax()->getValue());
        $this->assertEquals(3636.36, to_money($product->calculateAmountBeforeTax())->getValue());

        $product = $this->createProductWithPrice('inclusive', ['tax_rate' => 10, 'discount_type' => 'fixed', 'discount_total' => 200]);

        $this->assertEquals(3454.545, $product->amount_tax_exl);
        $this->assertEquals(3800, $product->calculateAmount());
        $this->assertEquals(200, $product->discountedAmount()->getValue());
        $this->assertEquals(345.45, $product->totalTax()->getValue());
        $this->assertEquals(3454.55, to_money($product->calculateAmountBeforeTax())->getValue());

        $product = $this->createProductWithPrice('inclusive', ['tax_rate' => 10, 'discount_type' => 'percent', 'discount_total' => 10]);

        $this->assertEquals(3272.727, $product->amount_tax_exl);
        $this->assertEquals(3600, $product->calculateAmount());
        $this->assertEquals(400, $product->discountedAmount()->getValue());
        $this->assertEquals(327.27, $product->totalTax()->getValue());
        $this->assertEquals(3272.73, to_money($product->calculateAmountBeforeTax())->getValue());
    }

    protected function createProductWithPrice($taxType = null, $attributes = [])
    {
        if ($taxType === 'no_tax' || $taxType === null) {
            $taxTypeMethod = 'noTax';
        } elseif ($taxType === 'exclusive') {
            $taxTypeMethod = 'taxExclusive';
        } else {
            $taxTypeMethod = 'taxInclusive';
        }

        $billable = Billable::factory()
            ->withBillableable()
            ->{$taxTypeMethod}()
            ->has(BillableProduct::factory(array_merge([
                'unit_price' => 2000,
                'qty' => 2,
                'tax_rate' => 0,
            ], $attributes)), 'products')->create();

        return $billable->products[0];
    }
}
