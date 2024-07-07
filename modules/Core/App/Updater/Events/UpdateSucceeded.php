<?php
 

namespace Modules\Core\App\Updater\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Core\App\Updater\Release;

class UpdateSucceeded
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Initialize new UpdateSucceeded instance.
     */
    public function __construct(public Release $release)
    {
    }

    /**
     * Get the release.
     */
    public function getRelease(): Release
    {
        return $this->release;
    }

    /**
     * Get the version number the installation was updated to.
     */
    public function getVersion(): string
    {
        return $this->release->getVersion();
    }
}
