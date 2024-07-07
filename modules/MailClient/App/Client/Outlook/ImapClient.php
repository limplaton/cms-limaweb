<?php
 

namespace Modules\MailClient\App\Client\Outlook;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Microsoft\Graph\Model\MailFolder;
use Microsoft\Graph\Model\Message as MessageModel;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchDeleteRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchGetRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchPatchRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchPostRequest;
use Modules\Core\App\Common\Microsoft\Services\Batch\BatchRequests;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\Core\App\Facades\MsGraph as Api;
use Modules\MailClient\App\Client\AbstractImapClient;
use Modules\MailClient\App\Client\Contracts\FolderInterface;
use Modules\MailClient\App\Client\Contracts\MessageInterface;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Client\Exceptions\FolderNotFoundException;
use Modules\MailClient\App\Client\Exceptions\MessageNotFoundException;
use Modules\MailClient\App\Client\Exceptions\ServiceUnavailableException;
use Modules\MailClient\App\Client\FolderIdentifier;
use Modules\MailClient\App\Client\MasksMessages;

class ImapClient extends AbstractImapClient
{
    use MasksFolders,
        MasksMessages,
        ProvidesMessageUri;

    /**
     * Initialize new ImapClient instance.
     */
    public function __construct(protected AccessTokenProvider $token)
    {
        Api::connectUsing($token);
    }

    /**
     * Get folder by id
     *
     * @param  string  $id  The folder identifier
     * @return \Modules\MailClient\App\Client\Outlook\Folder
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\FolderNotFoundException
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function getFolder($id)
    {
        try {
            // Temporary usage till Microsoft add the wellKnownName
            // property in their v1.0.0 API
            $originalVersion = tap(Api::getApiVersion(), function () {
                Api::setApiVersion('beta');
            });

            $request = Api::createGetRequest("/me/mailFolders/$id?\$expand=childFolders")
                ->setReturnType(MailFolder::class);

            $folder = $this->executeRequest($request);

            return tap($this->maskFolder($folder), function () use ($originalVersion) {
                Api::setApiVersion($originalVersion);
            });
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                throw new FolderNotFoundException;
            }

            throw $e;
        }
    }

    /**
     * Retrieve the account available folders from remote server
     *
     * @return \Modules\MailClient\App\Client\FolderCollection
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function retrieveFolders()
    {
        // Temporary usage till Microsoft add the wellKnownName
        // property in their v1.0.0 API
        $originalVersion = tap(Api::getApiVersion(), function () {
            Api::setApiVersion('beta');
        });

        $iterator = Api::createCollectionGetRequest('/me/mailFolders?$expand=childFolders')
            ->setReturnType(MailFolder::class);

        return tap($this->maskFolders($this->iterateRequest($iterator)), function () use ($originalVersion) {
            Api::setApiVersion($originalVersion);
        });
    }

    /**
     * Move a message to a given folder
     *
     *
     * @return bool
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function moveMessage(MessageInterface $message, FolderInterface $folder)
    {
        $request = Api::createPostRequest(
            "/me/messages/{$message->getId()}/move",
            ['destinationId' => $folder->getId()]
        );

        $response = $this->executeRequest($request);

        return $response->getStatus() === 201;
    }

    /**
     * Batch move messages to a given folder
     *
     * @param  array  $messages
     * @return array
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function batchMoveMessages($messages, FolderInterface $to, FolderInterface $from)
    {
        $requests = new BatchRequests;

        foreach ($messages as $messageId) {
            $moveUrl = "/me/mailFolders/{$from->getId()}/messages/{$messageId}/move";
            $requests->push(BatchPostRequest::make($moveUrl, ['destinationId' => $to->getId()]));
        }

        $map = [];

        if ($batchResponses = $this->executeRequest(Api::createBatchRequest($requests))) {
            foreach ($batchResponses as $response) {
                if (! isset($response['body']['error'])) {
                    $map[$messages[$response['id']]] = $response['body']['id'];
                }
            }
        }

        return $map;
    }

    /**
     * Permanently batch delete messages
     *
     * @param  array  $messages
     * @return void
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function batchDeleteMessages($messages)
    {
        $requests = new BatchRequests;

        foreach ($messages as $messageId) {
            $requests->push(BatchDeleteRequest::make("/me/messages/{$messageId}"));
        }

        $this->executeRequest(Api::createBatchRequest($requests));
    }

    /**
     * Batch mark as read messages
     *
     * @param  array  $messages
     * @return bool
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function batchMarkAsRead($messages, ?FolderIdentifier $folder = null)
    {
        return $this->batchModifyMessagesReadProperty($messages, true);
    }

    /**
     * Batch mark as unread messages
     *
     * @param  array  $messages
     * @return bool
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function batchMarkAsUnread($messages, ?FolderIdentifier $folder = null)
    {
        return $this->batchModifyMessagesReadProperty($messages, false);
    }

    /**
     * Get message by message identifier
     *
     * @param  string  $id
     * @return \Modules\MailClient\App\Client\Outlook\Message
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\MessageNotFoundException
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function getMessage($id, ?FolderIdentifier $folder = null)
    {
        try {
            $request = Api::createGetRequest($this->getMessageUri($id))
                ->setReturnType(MessageModel::class);

            return $this->maskMessage($this->executeRequest($request), Message::class);
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                throw new MessageNotFoundException;
            }

            throw $e;
        }
    }

    /**
     * Get messages
     *
     * @param  array  $params
     * @return \Illuminate\Support\Collection
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function getMessages($params = [])
    {
        $iterator = Api::createCollectionGetRequest('GET', $this->getMessagesUri($params))
            ->setReturnType(MessageModel::class);

        return $this->maskMessages($this->iterateRequest($iterator), Message::class);
    }

    /**
     * Get the latest message from the sent folder
     *
     * @return \Modules\MailClient\App\Client\Contracts\MessageInterface|null
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function getLatestSentMessage()
    {
        $request = Api::createCollectionGetRequest(
            $this->getFolderMessagesUri(
                $this->getSentFolder()->getId(),
                [
                    '$top' => 1,
                ]
            )
        )->setReturnType(MessageModel::class);

        if ($messages = $this->executeRequest($request)) {
            return $this->maskMessage($messages[0], Message::class);
        }
    }

    /**
     * Get get messages via batch request
     *
     * @param  array  $messages
     * @return \Illuminate\Support\Collection
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    public function batchGetMessages($messages)
    {
        $requests = new BatchRequests;

        foreach ($messages as $message) {
            $requests->push(BatchGetRequest::make($this->getMessageUri($message->getId())));
        }

        $batchMessages = [];

        if ($batchResponses = $this->executeRequest(Api::createBatchRequest($requests))) {
            foreach ($batchResponses as $response) {
                if ($response['status'] === 200) {
                    // Beacause Microsoft does not allow to set
                    // returnTypeModel for a batch request
                    // We need to manually create the model
                    $batchMessages[] = new MessageModel($response['body']);
                }
            }
        }

        return $this->maskMessages($batchMessages, Message::class);
    }

    /**
     * Execute the Microsoft request
     *
     * @param  \Microsoft\Graph\Http\GraphRequest  $request
     * @return mixed
     */
    protected function executeRequest($request)
    {
        try {
            return $request->execute();
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        } catch (ServerException $e) {
            if ($e->getCode() === 503) {
                throw new ServiceUnavailableException($e->getMessage(), now()->addMinute(1), $e);
            }

            throw $e;
        }
    }

    /**
     * Itereate the request pages and get all the data
     *
     * @param  \Microsoft\Graph\Http\GraphCollectionRequest  $iterator
     * @return array
     */
    protected function iterateRequest($iterator)
    {
        try {
            return Api::iterateCollectionRequest($iterator);
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        } catch (ServerException $e) {
            if ($e->getCode() === 503) {
                throw new ServiceUnavailableException($e->getMessage(), now()->addMinute(1), $e);
            }

            throw $e;
        }
    }

    /**
     * Batch modify the messages read property
     *
     * @param  array  $messages
     * @param  bool  $isRead
     * @return bool
     *
     * @throws \Modules\MailClient\App\Client\Exceptions\ConnectionErrorException
     */
    protected function batchModifyMessagesReadProperty($messages, $isRead)
    {
        $requests = new BatchRequests;

        foreach ($messages as $messageId) {
            $requests->push(BatchPatchRequest::make("/me/messages/{$messageId}", ['isRead' => $isRead]));
        }

        try {
            Api::createBatchRequest($requests)->execute();
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        } catch (ServerException $e) {
            if ($e->getCode() === 503) {
                throw new ServiceUnavailableException($e->getMessage(), now()->addMinute(1), $e);
            }

            throw $e;
        }

        return true;
    }
}
