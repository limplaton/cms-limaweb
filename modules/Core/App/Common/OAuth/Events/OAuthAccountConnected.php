<?php
 

namespace Modules\Core\App\Common\OAuth\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\App\Models\OAuthAccount;

class OAuthAccountConnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create new instance of OAuthAccountConnected.
     */
    public function __construct(public OAuthAccount $account)
    {
    }
}
