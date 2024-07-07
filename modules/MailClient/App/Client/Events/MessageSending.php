<?php
 

namespace Modules\MailClient\App\Client\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\MailClient\App\Client\Client;

class MessageSending
{
    use Dispatchable;

    /**
     * Create new MessageSending instance.
     */
    public function __construct(public Client $client)
    {
    }
}
