<?php
 

namespace Modules\Core\App\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** @mixin \Modules\Core\App\Models\Model */
trait HasDisplayOrder
{
    /**
     * Boot the HasDisplayOrder trait.
     */
    protected static function bootHasDisplayOrder()
    {
        static::addGlobalScope('displayOrder', fn (Builder $query) => $query->orderByDisplayOrder());
    }

    /**
     * Scope a query to order the model by "display_order" column.
     */
    public function scopeOrderByDisplayOrder(Builder $query): void
    {
        $query->orderBy('display_order');
    }
}
