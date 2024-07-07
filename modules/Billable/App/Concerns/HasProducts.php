<?php
 

namespace Modules\Billable\App\Concerns;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Billable\App\Models\Billable;

/** @mixin \Modules\Core\App\Models\Model */
trait HasProducts
{
    /**
     * Provide the total column to be updated whenever the billable is updated
     */
    public function totalColumn(): ?string
    {
        return null;
    }

    /**
     * Check whether the model billable has products
     */
    public function hasProducts(): bool
    {
        if ($this->relationLoaded('products')) {
            return $this->products->isNotEmpty();
        }

        if ($this->relationLoaded('billable') && $this->billable->relationLoaded('products')) {
            return $this->billable->products->isNotEmpty();
        }

        return $this->products()->count() > 0;
    }

    /**
     * Get the deal billable model
     */
    public function billable(): MorphOne
    {
        return $this->morphOne(Billable::class, 'billableable')->withDefault(array_filter([
            'tax_type' => Billable::defaultTaxType(),
        ]));
    }

    /**
     * Get all of the products for the model.
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            \Modules\Billable\App\Models\BillableProduct::class,
            \Modules\Billable\App\Models\Billable::class,
            'billableable_id',
            'billable_id',
            'id',
            'id'
        )->where('billableable_type', $this::class);
    }
}
