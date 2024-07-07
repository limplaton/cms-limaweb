<?php
 

namespace Modules\Core\App\Common\Google\Services\Message;

use Google\Client;

class MailReply extends Compose
{
    /**
     * Initialize new MailReply instance.
     */
    public function __construct(Client $client, protected Mail $reply)
    {
        parent::__construct($client);
    }

    /**
     * Creates the raw message content
     *
     * We will override the method in order to add
     * additional headers for the reply message just before
     * creating the raw content for the send method
     *
     * @return string
     */
    protected function createRawMessage()
    {
        $references = $this->reply->getReferences();
        $references[] = $this->reply->getInternetMessageId();

        $this->addSymfonyMessageInReplyToHeader($this->message, $this->reply->getInternetMessageId())
            ->addSymfonyMessageReferencesHeader($this->message, $references);

        return parent::createRawMessage();
    }

    /**
     * Get the message service
     *
     * @return \Google\Service\Gmail\Message
     */
    protected function getMessageService()
    {
        $service = parent::getMessageService();

        $service->setThreadId($this->reply->getThreadId());

        return $service;
    }
}
