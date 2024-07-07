<?php
 

namespace Modules\MailClient\App\Client\Compose;

use Modules\MailClient\App\Client\Client;
use Modules\MailClient\App\Client\FolderIdentifier;

class MessageReply extends AbstractComposer
{
    /**
     * Create new MessageReply instance.
     */
    public function __construct(
        Client $client,
        protected string|int $remoteId,
        protected FolderIdentifier $folder,
        ?FolderIdentifier $sentFolder = null
    ) {
        parent::__construct($client, $sentFolder);
    }

    /**
     * Reply to the message.
     *
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface
     */
    public function send()
    {
        return $this->client->reply($this->remoteId, $this->folder);
    }
}
