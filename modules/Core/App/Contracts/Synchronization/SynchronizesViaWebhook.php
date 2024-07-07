<?php
 

namespace Modules\Core\App\Contracts\Synchronization;

use Modules\Core\App\Models\Synchronization;

interface SynchronizesViaWebhook
{
    /**
     * Subscribe for changes for the given synchronization
     */
    public function watch(Synchronization $synchronization): void;

    /**
     * Unsubscribe from changes for the given synchronization
     */
    public function unwatch(Synchronization $synchronization): void;
}
