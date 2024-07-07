<?php
 

namespace Modules\Billable\Tests\Feature;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Billable\App\Enums\TaxType;
use Modules\Billable\App\Models\Billable;
use Modules\Billable\App\Models\BillableProduct;
use Modules\Deals\App\Models\Deal;
use Tests\TestCase;

class BillableModelTest extends TestCase
{
    public function test_can_determine_whether_billable_is_tax_exclusive()
    {
        $billable = new Billable(['tax_type' => TaxType::exclusive]);

        $this->assertTrue($billable->isTaxExclusive());
    }

    public function test_can_determine_whether_billable_is_tax_inclusive()
    {
        $billable = new Billable(['tax_type' => TaxType::inclusive]);

        $this->assertTrue($billable->isTaxInclusive());
    }

    public function test_can_determine_when_the_billable_has_no_tax()
    {
        $billable = new Billable(['tax_type' => TaxType::no_tax]);

        $this->assertFalse($billable->isTaxable());
    }

    public function test_it_can_calculate_billable_total_tax()
    {
        $noTax = $this->makeBillableWithProducts()->noTax()->create();
        $this->assertEquals(0, $noTax->totalTax()->getValue());

        $exclusive = $this->makeBillableWithProducts(['tax_rate' => 10])->taxExclusive()->create();
        $this->assertEquals(400, $exclusive->totalTax()->getValue());

        $inclusive = $this->makeBillableWithProducts(['tax_rate' => 10])->taxInclusive();
        $this->assertEquals(363.64, $inclusive->create()->totalTax()->getValue());
    }

    public function test_it_can_calculate_billable_subtotal()
    {
        $noTax = $this->makeBillableWithProducts()->noTax()->create();
        $this->assertEquals(4000, $noTax->subtotal()->getValue());

        $noTaxWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200]
        )->noTax()->create();
        $this->assertEquals(3600, $noTaxWithFixedDiscount->subtotal()->getValue());

        $noTaxWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10]
        )->noTax()->create();
        $this->assertEquals(3600, $noTaxWithPercentDiscount->subtotal()->getValue());

        // Exclusive
        $exclusive = $this->makeBillableWithProducts(['tax_rate' => 10])->taxExclusive()->create();
        $this->assertEquals(4000, $exclusive->subtotal()->getValue());

        $exclusiveWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200, 'tax_rate' => 10]
        )->taxExclusive()->create();
        $this->assertEquals(3600, $exclusiveWithFixedDiscount->subtotal()->getValue());

        $exclusiveWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10, 'tax_rate' => 10]
        )->taxExclusive()->create();
        $this->assertEquals(3600, $exclusiveWithPercentDiscount->subtotal()->getValue());

        // Inclusive
        $inclusive = $this->makeBillableWithProducts(['tax_rate' => 10])->taxInclusive()->create();
        $this->assertEquals(4000, $inclusive->subtotal()->getValue());

        $inclusiveWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200, 'tax_rate' => 10]
        )->taxInclusive()->create();
        $this->assertEquals(3600, $inclusiveWithFixedDiscount->subtotal()->getValue());

        $inclusiveWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10, 'tax_rate' => 10]
        )->taxInclusive()->create();
        $this->assertEquals(3600, $inclusiveWithPercentDiscount->subtotal()->getValue());
    }

    public function test_no_tax_billable_total_is_calculated_properly()
    {
        $noTax = $this->makeBillableWithProducts()->noTax()->create();
        $this->assertEquals(4000, $noTax->total()->getValue());

        $noTaxWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200]
        )->noTax()->create();
        $this->assertEquals(3600, $noTaxWithFixedDiscount->total()->getValue());

        $noTaxWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10]
        )->noTax()->create();
        $this->assertEquals(3600, $noTaxWithPercentDiscount->total()->getValue());
    }

    public function test_tax_exclusive_billable_total_is_calculated_properly()
    {
        $exclusive = $this->makeBillableWithProducts(['tax_rate' => 10])->taxExclusive()->create();
        $this->assertEquals(4400, $exclusive->total()->getValue());

        $exclusiveWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200, 'tax_rate' => 10]
        )->taxExclusive()->create();
        $this->assertEquals(3960, $exclusiveWithFixedDiscount->total()->getValue());

        $exclusiveWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10, 'tax_rate' => 10]
        )->taxExclusive()->create();
        $this->assertEquals(3960, $exclusiveWithPercentDiscount->total()->getValue());
    }

    public function test_tax_inclusive_billable_total_is_calculated_properly()
    {
        $inclusive = $this->makeBillableWithProducts(['tax_rate' => 10])->taxInclusive()->create();
        $this->assertEquals(4000, $inclusive->total()->getValue());

        $inclusiveWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200, 'tax_rate' => 10]
        )->taxInclusive()->create();
        $this->assertEquals(3600, $inclusiveWithFixedDiscount->total()->getValue());

        $inclusiveWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10, 'tax_rate' => 10]
        )->taxInclusive()->create();
        $this->assertEquals(3600, $inclusiveWithPercentDiscount->total()->getValue());
    }

    public function test_it_can_calculate_billable_total_discount()
    {
        $noTaxWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200]
        )->noTax()->create();
        $this->assertEquals(400, $noTaxWithFixedDiscount->discountedAmount()->getValue());

        $noTaxWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10]
        )->noTax()->create();
        $this->assertEquals(400, $noTaxWithPercentDiscount->discountedAmount()->getValue());

        // Exclusive
        $exclusiveWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200, 'tax_rate' => 10]
        )->taxExclusive()->create();
        $this->assertEquals(400, $exclusiveWithFixedDiscount->discountedAmount()->getValue());

        $exclusiveWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10, 'tax_rate' => 10]
        )->taxExclusive()->create();
        $this->assertEquals(400, $exclusiveWithPercentDiscount->discountedAmount()->getValue());

        // Inclusive
        $inclusiveWithFixedDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200, 'tax_rate' => 10]
        )->taxInclusive()->create();
        $this->assertEquals(400, $inclusiveWithFixedDiscount->discountedAmount()->getValue());

        $inclusiveWithPercentDiscount = $this->makeBillableWithProducts(
            ['discount_type' => 'percent', 'discount_total' => 10, 'tax_rate' => 10]
        )->taxInclusive()->create();
        $this->assertEquals(400, $inclusiveWithPercentDiscount->discountedAmount()->getValue());
    }

    public function test_can_determine_if_billable_has_discount_applied()
    {
        $billable = $this->makeBillableWithProducts(
            ['discount_type' => 'fixed', 'discount_total' => 200]
        )->taxInclusive()->create();

        $this->assertTrue($billable->hasDiscount());
    }

    public function test_billable_taxes_are_unique()
    {
        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->create();

        $products = BillableProduct::factory()->count(4)->state(new Sequence(
            ['tax_label' => 'TAX1', 'tax_rate' => 10],
            ['tax_label' => 'TAX1', 'tax_rate' => 10],
            ['tax_label' => 'TAX1', 'tax_rate' => 15],
            ['tax_label' => 'TAX4', 'tax_rate' => 15],
        ))->make();

        $billable->products()->saveMany($products);

        $this->assertCount(3, $billable->taxes());
    }

    public function test_billable_taxes_are_calculated_properly()
    {
        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->has(BillableProduct::factory(['tax_label' => 'TAX1', 'tax_rate' => 10, 'unit_price' => 2000]), 'products')
            ->has(BillableProduct::factory(['tax_label' => 'TAX1', 'tax_rate' => 10, 'unit_price' => 2000]), 'products')
            ->has(BillableProduct::factory(['tax_label' => 'TAX2', 'tax_rate' => 15, 'unit_price' => 2000]), 'products')
            ->create();

        $taxes = $billable->taxes();

        $this->assertEquals(400, $taxes[0]['total']->getValue());
        $this->assertEquals(300, $taxes[1]['total']->getValue());
    }

    public function test_billable_has_billableable()
    {
        $billable = Billable::factory()->withBillableable()->create();

        $this->assertInstanceOf(Deal::class, $billable->billableable);
    }

    public function test_billable_has_products()
    {
        $billable = $this->makeBillableWithProducts()->create();

        $this->assertCount(2, $billable->products);
    }

    protected function makeBillableWithProducts($attributes = [])
    {
        $callback = function () use ($attributes) {
            return array_merge([
                'unit_price' => 2000,
                'qty' => 1,
                'tax_rate' => 0,
            ], $attributes);
        };

        return Billable::factory()->withBillableable()
            ->has(BillableProduct::factory()->count(2)->state($callback), 'products');
    }
}
