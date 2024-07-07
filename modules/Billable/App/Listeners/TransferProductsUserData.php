<?php
 

namespace Modules\Billable\App\Listeners;

use Modules\Billable\App\Models\Product;
use Modules\Users\App\Events\TransferringUserData;

class TransferProductsUserData
{
    /**
     * Handle the event.
     */
    public function handle(TransferringUserData $event): void
    {
        Product::withTrashed()->where('created_by', $event->fromUserId)->update(['created_by' => $event->toUserId]);
    }
}
