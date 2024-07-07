<?php
 

namespace Modules\Users\App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Users\App\Models\User;

class TransferringUserData
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $toUserId, public int $fromUserId, public User $fromUser)
    {
        //
    }
}
