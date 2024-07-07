<?php
 

namespace Modules\Core\App\Updater\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Core\App\Updater\Patch;

class PatchApplied
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Initialize new PatchApplied instance.
     */
    public function __construct(public Patch $patch)
    {
    }

    /**
     * Get the patch that was applied.
     */
    public function getPatch(): Patch
    {
        return $this->patch;
    }
}
