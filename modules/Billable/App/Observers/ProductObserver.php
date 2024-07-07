<?php
 

namespace Modules\Billable\App\Observers;

use Modules\Billable\App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "deleting" event.
     */
    public function deleting(Product $product): void
    {
        if ($product->isForceDeleting()) {
            $product->loadMissing('billables')->billables->each->delete();
        }
    }
}
