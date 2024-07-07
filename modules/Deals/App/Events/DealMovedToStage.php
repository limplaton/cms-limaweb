<?php
 

namespace Modules\Deals\App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Deals\App\Models\Deal;
use Modules\Deals\App\Models\Stage;

class DealMovedToStage
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create new DealMovedToStage instance.
     */
    public function __construct(public Deal $deal, public Stage $previousStage)
    {
    }
}
