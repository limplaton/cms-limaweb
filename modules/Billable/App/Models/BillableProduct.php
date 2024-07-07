<?php
 

namespace Modules\Billable\App\Models;

use Akaunting\Money\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billable\Database\Factories\BillableProductFactory;
use Modules\Core\App\Concerns\HasDisplayOrder;
use Modules\Core\App\Models\Model;

class BillableProduct extends Model
{
    use HasDisplayOrder, HasFactory;

    /**
     * The relationships that should always be loaded.
     *
     * Avoid lazy loading violation exception when saving products to Billable
     *
     * @var array
     */
    protected $with = ['billable', 'originalProduct'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'unit_price',
        'qty',
        'unit',
        'tax_rate',
        'tax_label',
        'discount_type',
        'discount_total',
        'display_order',
        'note',
        'product_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:3',
        'tax_rate' => 'decimal:3',
        'qty' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'amount' => 'decimal:3',
        'amount_tax_exl' => 'decimal:3',
        'billable_id' => 'int',
        'display_order' => 'int',
        'product_id' => 'int',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::saving(function (BillableProduct $model) {
            $model->amount = $model->calculateAmount();
            $model->amount_tax_exl = $model->calculateAmountBeforeTax();

            if (! $model->billable->isTaxable()) {
                $model->tax_rate = 0;
            }
        });
    }

    /**
     * Get the underlying original product.
     *
     * NOTE: If deleted, the original product may be null
     */
    public function originalProduct(): BelongsTo
    {
        return $this->belongsTo(\Modules\Billable\App\Models\Product::class, 'product_id');
    }

    /**
     * Get the sku attribute.
     */
    protected function sku(): Attribute
    {
        return Attribute::get(
            fn () => $this->originalProduct?->sku
        );
    }

    /**
     * A product belongs to a billable model.
     */
    public function billable(): BelongsTo
    {
        return $this->belongsTo(Billable::class);
    }

    /**
     * Get the product unit price in "Money" instance.
     */
    public function unitPrice(): Money
    {
        return to_money($this->unit_price);
    }

    /**
     * Get the product amount in "Money" instance.
     */
    public function amount(): Money
    {
        return to_money($this->amount);
    }

    /**
     * Get the subtotal of the product.
     */
    public function subtotal(): Money
    {
        return to_money($this->rawSubtotal());
    }

    /**
     * Get the product raw subtotal.
     */
    public function rawSubtotal(): float
    {
        return floatval($this->qty) * floatval($this->unit_price);
    }

    /**
     * Get the product total discounted amount in "Money" instance.
     */
    public function discountedAmount(): Money
    {
        return to_money($this->rawDiscountedAmount());
    }

    /**
     * Get the product raw discounted amount.
     */
    public function rawDiscountedAmount(): float
    {
        $discount = floatval($this->discount_total);

        if ($this->discount_type === 'fixed') {
            return $discount;
        }

        $discountPercentDecimal = $discount / 100;

        return $discountPercentDecimal * $this->rawSubtotal();
    }

    /**
     * Get the product total tax in "Money" instance.
     */
    public function totalTax(): Money
    {
        return to_money($this->rawTotalTax());
    }

    /**
     * Get the product raw total tax.
     */
    public function rawTotalTax(): float
    {
        $discountedAmount = $this->rawDiscountedAmount();
        $taxableAmount = $this->rawSubtotal() - $discountedAmount;

        if ($this->billable->isTaxInclusive()) {
            return $taxableAmount - ($taxableAmount / (1 + ($this->tax_rate / 100)));
        } else {
            return $taxableAmount * ($this->tax_rate / 100);
        }
    }

    /**
     * Get the product tax rate.
     */
    protected function taxRate(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => floatval($value),
        );
    }

    /**
     * Calculate the product total amount with any discounts included.
     */
    public function calculateAmount(): float
    {
        return $this->rawSubtotal() - $this->rawDiscountedAmount();
    }

    /**
     * Calculate the product amount before tax.
     */
    public function calculateAmountBeforeTax(): float
    {
        if (! $this->billable->isTaxable() || $this->billable->isTaxExclusive()) {
            return $this->calculateAmount();
        }

        return ($this->rawSubtotal() - $this->rawDiscountedAmount()) / (1 + ($this->tax_rate / 100));
    }

    /**
     * Get the billable products default discount type.
     */
    public static function defaultDiscountType(): ?string
    {
        return settings('discount_type');
    }

    /**
     * Set the billable products default discount type.
     */
    public static function setDefaultDiscountType(?string $value): void
    {
        settings(['discount_type' => $value]);
    }

    /**
     * Get the billable products default tax label.
     */
    public static function defaultTaxLabel(): ?string
    {
        return settings('tax_label');
    }

    /**
     * Set the billable products default tax label.
     */
    public static function setDefaultTaxLabel(?string $value): void
    {
        settings(['tax_label' => $value]);
    }

    /**
     * Get the billable products default tax rate.
     */
    public static function defaultTaxRate(): float|int|null
    {
        return settings('tax_rate');
    }

    /**
     * Set the billable products default tax label.
     */
    public static function setDefaultTaxRate(float|int|null $value): void
    {
        settings(['tax_rate' => $value]);
    }

    /**
     * Get the document attributes that are used in a form.
     */
    public static function formAttributes(): array
    {
        return [
            'name',
            'description',
            'unit_price',
            'qty',
            'discount_total',
            'discount_type',
            'tax_rate',
            'tax_label',
            'unit',
            'note',
            'product_id',
            'display_order',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BillableProductFactory
    {
        return BillableProductFactory::new();
    }
}
