<?php
 

namespace Modules\Core\App\Contracts\Synchronization;

use Modules\Core\App\Models\Synchronization;

interface Synchronizable
{
    /**
     * Synchronize the data for the given synchronization
     */
    public function synchronize(Synchronization $synchronization): void;
}
