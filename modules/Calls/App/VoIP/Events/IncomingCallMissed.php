<?php
 

namespace Modules\Calls\App\VoIP\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Calls\App\VoIP\Call;

class IncomingCallMissed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create new instance of IncomingCallMissed.
     */
    public function __construct(public Call $call)
    {
    }
}
