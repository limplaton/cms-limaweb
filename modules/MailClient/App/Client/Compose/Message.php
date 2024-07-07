<?php
 

namespace Modules\MailClient\App\Client\Compose;

class Message extends AbstractComposer
{
    /**
     * Send a new message.
     *
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    public function send()
    {
        return $this->client->send();
    }
}
