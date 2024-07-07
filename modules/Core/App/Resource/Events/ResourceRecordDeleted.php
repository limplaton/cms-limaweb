<?php
 

namespace Modules\Core\App\Resource\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;

class ResourceRecordDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create new ResourceRecordDeleted instance.
     */
    public function __construct(public Model $model, public Resource $resource)
    {
    }
}
