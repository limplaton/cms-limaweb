<?php
 

namespace Modules\MailClient\App\Client\Gmail;

use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Str;
use Modules\Core\App\Common\OAuth\AccessTokenProvider;
use Modules\Core\App\Facades\Google as Client;
use Modules\MailClient\App\Client\AbstractImapClient;
use Modules\MailClient\App\Client\Contracts\FolderInterface;
use Modules\MailClient\App\Client\Contracts\MessageInterface;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Client\Exceptions\FolderNotFoundException;
use Modules\MailClient\App\Client\Exceptions\MessageNotFoundException;
use Modules\MailClient\App\Client\Exceptions\RateLimitExceededException;
use Modules\MailClient\App\Client\Exceptions\ServiceUnavailableException;
use Modules\MailClient\App\Client\Exceptions\UnauthorizedException;
use Modules\MailClient\App\Client\FolderCollection;
use Modules\MailClient\App\Client\FolderIdentifier;

class ImapClient extends AbstractImapClient
{
    /**
     * Ignore folders by id
     *
     * @var array
     */
    protected $ignoredFoldersById = [
        'UNREAD',
        'CHAT',
        'STARRED',
        'PERSONAL', // Personal label is not even shown in Gmail
    ];

    /**
     * Create new ImapClient instance.
     */
    public function __construct(protected AccessTokenProvider $token)
    {
        Client::connectUsing($token);
    }

    /**
     * Get folder by a given id
     *
     * @param  string  $id  The folder identifier
     * @return \Modules\MailClient\App\Client\Outlook\Folder
     */
    public function getFolder($id)
    {
        try {
            return $this->exceptionHandler(
                fn () => $this->maskFolder(Client::labels()->get($id))
            );
        } catch (GoogleServiceException $e) {
            if ($e->getCode() === 404) {
                throw new FolderNotFoundException($e->getMessage(), $e->getCode(), $e);
            }

            throw $e;
        }
    }

    /**
     * Retrieve the account available folders from remote server
     *
     * @return \Modules\MailClient\App\Client\FolderCollection
     */
    public function retrieveFolders()
    {
        return $this->exceptionHandler(
            fn () => $this->maskFolders(Client::labels()->list())->createTreeFromDelimiter(Folder::DELIMITER)
        );
    }

    /**
     * Get message by message identifier
     *
     * @param  string  $id
     * @return \Modules\MailClient\App\Client\Gmail\Message
     */
    public function getMessage($id, ?FolderIdentifier $folder = null)
    {
        try {
            return $this->exceptionHandler(
                fn () => new Message(Client::message()->get($id))
            );
        } catch (GoogleServiceException $e) {
            if ($e->getCode() === 404) {
                throw new MessageNotFoundException($e->getMessage(), $e->getCode(), $e);
            }

            throw $e;
        }
    }

    /**
     * Get all account messages
     *
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getMessages($limit = 50)
    {
        return $this->exceptionHandler(
            fn () => Client::message()->take($limit)
                ->preload(Message::class)
                ->includeSpamTrash()
                ->all()
        );
    }

    /**
     * Move a message to a given folder
     *
     * @todo  TEST THIS METHOD
     *
     * @return bool
     */
    public function moveMessage(MessageInterface $message, FolderInterface $folder)
    {
        return $this->exceptionHandler(
            fn () => (bool) $this->getMessage($message->getId())->addLabel($folder->getName())
        );
    }

    /**
     * Batch move messages to a given folder
     *
     * @param  array  $messages
     * @return bool
     */
    public function batchMoveMessages($messages, FolderInterface $to, FolderInterface $from)
    {
        return $this->exceptionHandler(function () use ($messages, $to, $from) {
            // Gmail doesn't allow removing the "SENT" or "DRAFT" label, in this case
            // we don't pass any label to remove, only pass to add the label and Gmail will do it's job
            $removeLabels = $from->supportMove() ? [$from->getId()] : [];
            $addLabels = [$to->getId()];

            return Client::message()->batchModify($messages, $removeLabels, $addLabels);
        });
    }

    /**
     * Permanently batch delete messages
     *
     * @param  array  $messages
     * @return void
     */
    public function batchDeleteMessages($messages)
    {
        $this->exceptionHandler(
            fn () => Client::message()->batchDelete($messages)
        );
    }

    /**
     * Batch mark as read messages
     *
     * @param  array  $messages
     * @return bool
     */
    public function batchMarkAsRead($messages, ?FolderIdentifier $folder = null)
    {
        return $this->exceptionHandler(
            fn () => Client::message()->batchModify($messages, ['UNREAD'])
        );
    }

    /**
     * Batch mark as unread messages
     *
     * @param  array  $messages
     * @return bool
     */
    public function batchMarkAsUnread($messages, ?FolderIdentifier $folder = null)
    {
        return $this->exceptionHandler(
            fn () => Client::message()->batchModify($messages, [], ['UNREAD'])
        );
    }

    /**
     * Batch get messages
     *
     * @param  array|\Illuminate\Support\Collection  $messages
     * @return \Illuminate\Support\Collection
     */
    public function batchGetMessages($messages)
    {
        return $this->exceptionHandler(
            fn () => Client::message()
                ->batchRequest($messages)
                ->mapInto(Message::class)
        );
    }

    /**
     * Get the latest message from the sent folder
     *
     * @return \Modules\MailClient\App\Client\Gmail\Message|null
     */
    public function getLatestSentMessage()
    {
        return $this->exceptionHandler(function () {
            $messages = Client::message()->take(1)->in('sent')->preload()->all();

            if ($message = $messages->first()) {
                return new Message($message);
            }

            return null;
        });
    }

    /**
     * Get mailbox history
     *
     * https://developers.google.com/gmail/api/v1/reference/users/history/list
     *
     * @param  int  $historyId
     * @param  array  $optParams
     * @return \Illuminate\Support\Collection
     */
    public function getHistory($historyId, $optParams = [])
    {
        return $this->exceptionHandler(function () use ($historyId, $optParams) {
            $params = array_merge(['startHistoryId' => intval($historyId)], $optParams);

            return Client::history()->get($params);
        });
    }

    /**
     * Common exceptions handler
     *
     * @param  \Closure  $closure
     * @return mixed
     */
    protected function exceptionHandler($closure)
    {
        try {
            return $closure();
        } catch (GoogleServiceException $e) {
            $message = $e->getErrors()[0]['message'] ?? $e->getMessage();

            if ($e->getCode() == 401) {
                throw new ConnectionErrorException($message, $e->getCode(), $e);
            } elseif ($e->getCode() === 503) {
                // TODO: Check if Google returns "Retry-After" header for service unavailable exception.
                throw new ServiceUnavailableException($message, null, $e);
            } elseif ($this->isRateLimitExceededException($e)) {
                $errors = $e->getErrors();

                $retryAfter = Str::after(
                    $errors['message'] ?? $errors[0]['message'] ?? '', 'Retry after '
                );

                throw new RateLimitExceededException($e->getMessage(), $retryAfter, $e);
            } elseif ($e->getCode() == 403) {
                throw new UnauthorizedException($message, $e->getCode(), $e);
            }

            throw $e;
        }
    }

    /**
     * Check whether the given exception is rate limit exceeded.
     *
     * @see https://developers.google.com/gmail/api/guides/handle-errors#resolve_a_403_error_usage_limit_exceeded
     */
    protected function isRateLimitExceededException(GoogleServiceException $e): bool
    {
        if (! in_array($e->getCode(), [403, 429])) {
            return false;
        }

        $errors = $e->getErrors();
        $reason = $errors['reason'] ?? $errors[0]['reason'];

        return in_array($reason, ['rateLimitExceeded', 'userRateLimitExceeded', 'dailyLimitExceeded']);
    }

    /**
     * Mask folders
     *
     * @param  array  $folders
     * @return \Modules\MailClient\App\Client\FolderCollection
     */
    protected function maskFolders($folders)
    {
        return (new FolderCollection($folders))->map(function ($folder) {
            return $this->maskFolder($folder);
        })->reject(function ($folder) {
            // Email account draft folders are not supported
            return in_array($folder->getId(), $this->ignoredFoldersById) || $folder->isDraft();
        })->values();
    }

    /**
     * Mask folder
     *
     * @param  mixed  $folder
     * @return \Modules\MailClient\App\Client\Gmail\Folder
     */
    protected function maskFolder($folder)
    {
        return new Folder($folder);
    }
}
