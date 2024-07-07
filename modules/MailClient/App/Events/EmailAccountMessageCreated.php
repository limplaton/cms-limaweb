<?php
 

namespace Modules\MailClient\App\Events;

use Illuminate\Queue\SerializesModels;
use Modules\MailClient\App\Client\Contracts\MessageInterface;
use Modules\MailClient\App\Models\EmailAccountMessage;

class EmailAccountMessageCreated
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public EmailAccountMessage $message, public MessageInterface $remoteMessage)
    {
    }
}
