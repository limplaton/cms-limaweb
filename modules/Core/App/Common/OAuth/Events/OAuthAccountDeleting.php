<?php
 

namespace Modules\Core\App\Common\OAuth\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\App\Models\OAuthAccount;

class OAuthAccountDeleting
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public OAuthAccount $account)
    {
        //
    }
}
