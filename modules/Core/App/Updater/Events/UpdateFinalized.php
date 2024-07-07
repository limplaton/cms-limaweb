<?php
 

namespace Modules\Core\App\Updater\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class UpdateFinalized
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Initialize new UpdateFinalized instance.
     */
    public function __construct(public string $version, public string $oldVersion)
    {
    }

    /**
     * Get the version number the installation was updated to.
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
