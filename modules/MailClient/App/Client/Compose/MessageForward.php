<?php
 

namespace Modules\MailClient\App\Client\Compose;

class MessageForward extends MessageReply
{
    /**
     * Forward the message.
     *
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    public function send()
    {
        return $this->client->forward($this->remoteId, $this->folder);
    }
}
