<?php
 

namespace Modules\Billable\App\Models;

use Akaunting\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Billable\App\Enums\TaxType;
use Modules\Billable\Database\Factories\BillableFactory;
use Modules\Core\App\Models\Model;

class Billable extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tax_type' => TaxType::class,
    ];

    /**
     * Indicates if the model has timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tax_type',
        //'terms',
        //'notes'
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::deleting(function (Billable $model) {
            $model->products->each(function (BillableProduct $product) {
                $product->delete();
            });
        });
    }

    /**
     * Get all of the billable instance products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(BillableProduct::class);
    }

    /**
     * Get the owning billableable model.
     */
    public function billableable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the billable subtotal amount.
     */
    public function subtotal(): Money
    {
        return to_money($this->rawSubtotal());
    }

    /**
     * Get the billable unformatted subtotal amount.
     */
    public function rawSubtotal(): float
    {
        return $this->products->reduce(function ($total, $product) {
            return $total += $product->calculateAmount();
        }, 0.0);
    }

    /**
     * Get the billable total amount.
     */
    public function total(): Money
    {
        return to_money($this->rawTotal());
    }

    /**
     * Get the billable unformatted total amount.
     */
    public function rawTotal(): float
    {
        return $this->rawSubtotal() + (! $this->isTaxInclusive() ? $this->rawTotalTax() : 0);
    }

    /**
     * Get the billable total tax amount.
     */
    public function totalTax(): Money
    {
        return to_money($this->rawTotalTax());
    }

    /**
     * Get the billable unformatted total tax amount.
     */
    public function rawTotalTax(): float
    {
        return collect($this->taxes())->reduce(function (float $total, array $tax) {
            return $total + $tax['raw_total'];
        }, 0.0);
    }

    /**
     * Get the billable total discounted amount.
     */
    public function discountedAmount(): Money
    {
        return to_money($this->rawDiscountedAmount());
    }

    /**
     * Get the billable unformatted total discounted amount.
     */
    public function rawDiscountedAmount(): float
    {
        return $this->products->reduce(function (float $total, BillableProduct $product) {
            return $total + $product->rawDiscountedAmount();
        }, 0.0);
    }

    /**
     * Check whether the billable has discount applied.
     */
    public function hasDiscount(): bool
    {
        return $this->discountedAmount()->isPositive();
    }

    /**
     * Check whether the billable is tax exclusive.
     */
    public function isTaxExclusive(): bool
    {
        return $this->tax_type === TaxType::exclusive;
    }

    /**
     * Check whether the billable is tax inclusive.
     */
    public function isTaxInclusive(): bool
    {
        return $this->tax_type === TaxType::inclusive;
    }

    /**
     * Check whether the billable is taxable.
     */
    public function isTaxable(): bool
    {
        return $this->tax_type !== TaxType::no_tax;
    }

    /**
     * Get the applied taxes on the billable.
     */
    public function taxes(): array
    {
        if (! $this->isTaxable()) {
            return [];
        }

        return $this->products->unique(function ($product) {
            return $product->tax_label.$product->tax_rate;
        })
            ->sortBy('tax_rate')
            ->where('tax_rate', '>', 0)
            ->reduce(function ($groups, $tax) {
                $groups[] = [
                    'key' => $tax->tax_label.$tax->tax_rate,
                    'rate' => $tax->tax_rate,
                    'label' => $tax->tax_label,
                    'raw_total' => $total = $this->calculatetotalTax($tax),
                    'total' => to_money($total),
                ];

                return $groups;
            }, []);
    }

    /**
     * Calculate the total tax amount for the given tax.
     */
    protected function calculatetotalTax(object $tax)
    {
        return $this->products->filter(function (BillableProduct $product) use ($tax) {
            return $product->tax_label === $tax->tax_label && $product->tax_rate === $tax->tax_rate;
        })->reduce(function (float $total, BillableProduct $product) {
            return $total + $this->calculateTotalTaxInAmount(
                $product->calculateAmount(),
                $product->tax_rate,
                $this->isTaxInclusive()
            );
        }, 0.0);
    }

    /**
     * Calculate total tax in the given amount for the given tax rate.
     */
    protected function calculateTotalTaxInAmount(float $amount, string|int|float $taxRate, bool $inclusive): float
    {
        $taxRate = floatval($taxRate);

        if ($inclusive) {
            return $amount - ($amount / (1 + ($taxRate / 100)));
        }

        return $amount * ($taxRate / 100);
    }

    /**
     * Get the billable products default tax type.
     */
    public static function defaultTaxType(): ?TaxType
    {
        $default = settings('tax_type');

        if ($default) {
            return TaxType::find($default);
        }

        return null;
    }

    /**
     * Set the billable products default tax type.
     */
    public static function setDefaultTaxType(null|string|TaxType $value): void
    {
        settings(['tax_type' => $value instanceof TaxType ? $value->name : $value]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BillableFactory
    {
        return BillableFactory::new();
    }
}
