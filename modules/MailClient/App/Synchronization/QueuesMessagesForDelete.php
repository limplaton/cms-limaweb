<?php
 

namespace Modules\MailClient\App\Synchronization;

use Modules\MailClient\App\Models\EmailAccountFolder;
use Modules\MailClient\App\Models\EmailAccountMessage;

trait QueuesMessagesForDelete
{
    /**
     * Messages queued for delete.
     */
    protected array $queuedForDelete = [];

    /**
     * Get a message from the delete queue.
     *
     * @param  string  $subject
     * @param  string  $messageId
     */
    protected function getMessageFromDeleteQueue($subject, $messageId): ?EmailAccountMessage
    {
        foreach ($this->getDeleteQueueKeys() as $folderKey) {
            $index = $this->getQueuedMessageForDeleteIndex($folderKey, $subject, $messageId);
            if (! is_null($index)) {
                return $this->queuedForDelete[$folderKey][$index];
            }
        }

        return null;
    }

    /**
     * Adds a new message to the delete queue.
     */
    protected function addMessageToDeleteQueue(string|int $remoteId, EmailAccountFolder $folder): void
    {
        $key = $this->createDeleteQueueKey($folder->id);
        $this->queuedForDelete[$key] ??= [];

        // Only messages that exists in local database are queued for delete
        if ($message = $this->findDatabaseMessageViaRemoteId($remoteId, $folder)) {
            $this->queuedForDelete[$key][] = $message;
        }
    }

    /**
     * Remove message from the delete queue.
     *
     * @param  string  $subject
     * @param  string  $messageId
     */
    protected function removeMessageFromDeleteQueue($subject, $messageId): void
    {
        foreach ($this->getDeleteQueueKeys() as $folderKey) {
            $index = $this->getQueuedMessageForDeleteIndex($folderKey, $subject, $messageId);
            if (! is_null($index)) {
                unset($this->queuedForDelete[$folderKey][$index]);

                break;
            }
        }
    }

    /**
     * Delete all messages which are queued for delete.
     */
    protected function deleteQueuedMessages(): void
    {
        if ($this->account->isSyncOnHold()) {
            return;
        }

        foreach ($this->queuedForDelete as $key => $messages) {
            [$string, $folderId] = explode('-', $key);

            foreach ($messages as $message) {
                $this->deleteMessage($message->remote_id, EmailAccountFolder::find($folderId));
            }
        }

        $this->queuedForDelete = [];
    }

    /**
     * Get the queued message for delete index
     *
     * @param  string  $queueKey
     * @param  string  $subject
     * @param  string  $messageId
     */
    protected function getQueuedMessageForDeleteIndex($queueKey, $subject, $messageId): ?int
    {
        foreach ($this->queuedForDelete[$queueKey] as $index => $message) {
            if ($message->subject == $subject && $message->message_id == $messageId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Get the queued messages keys.
     */
    protected function getDeleteQueueKeys(): array
    {
        return array_keys($this->queuedForDelete);
    }

    /**
     * Create the queue delete key.
     */
    protected function createDeleteQueueKey(int $folderId): string
    {
        return 'folder-'.$folderId;
    }
}
