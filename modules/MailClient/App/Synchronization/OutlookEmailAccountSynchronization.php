<?php
 

namespace Modules\MailClient\App\Synchronization;

use Illuminate\Support\Collection;
use Modules\MailClient\App\Models\EmailAccountFolder;

class OutlookEmailAccountSynchronization extends EmailAccountIdBasedSynchronization
{
    /**
     * The delta meta key.
     */
    const DELTA_META_KEY = 'deltaLink';

    /**
     * Mode for the sync process.
     */
    protected string $mode = self::FORCE_MODE;

    /**
     * Start account messages synchronization.
     */
    public function syncMessages(): void
    {
        $folders = $this->account->activeFolders();
        $messages = $this->getInitialDataAndQueueMessagesForDelete($folders->all());

        foreach ($folders as $folder) {
            if (array_key_exists($folder->id, $messages)) {
                $this->info(sprintf('Performing sync for folder %s via delta link.', $folder->name));

                $this->processMessages($messages[$folder->id]);
            }
        }
    }

    /**
     * Get all messages and queue deleted messages
     * so we can use the messages from the queue to handle moved messages.
     */
    protected function getInitialDataAndQueueMessagesForDelete(array $folders): array
    {
        $data = [];

        foreach ($folders as $folder) {
            /** @var \Modules\MailClient\App\Client\Outlook\Folder */
            $remoteFolder = $this->findFolder($folder);

            /**
             * @todo https://github.com/microsoftgraph/msgraph-sdk-php/issues/68
             */
            $messages = $remoteFolder->getDeltaMessages(
                $folder->getMeta(static::DELTA_META_KEY), // current delta link
                $this->account->initial_sync_from->format('Y-m-d H:i:s')
            );

            $newDeltaLink = $messages->getDeltaLink();

            /**
             * Check if it's trash or spam folder and there is no initial sync for the account
             * If yes, only save the new delta link as trash or spam are not synced on the inital sync
             */
            if (! $this->account->isInitialSyncPerformed() && $remoteFolder->isTrashOrSpam()) {
                $folder->setMeta(static::DELTA_META_KEY, $newDeltaLink);

                continue;
            }

            /**
             * Make the messages unique based on their ID as Microsoft
             * does not guarantee that the messages in delta will be unique
             * then we will batch get every message
             */
            $messages = $messages->unique(fn ($message) => $message->getId())->values();

            /**
             * Queue messages for removal first and remove it from the messages collection as we don't need them
             */
            if (! $this->isFolderInitialSync($folder)) {
                $messages = $this->checkForRemovedMessages($messages, $folder);
            }

            /**
             * We need to get all changes messages via batch so we can perform full
             * update to the message in case the message exists in database
             * The function also will retrieve openExtensions and the headers e.q. references
             * and in-reply to because it's not possible to retieve the headers via delta as
             * Microsoft does not return them for sent messages
             */
            $data[$folder->id] = $this->getImapClient()->batchGetMessages($messages);

            /**
             * And after processing update the folder delta link
             * In case of failures to catch the messages again
             * Because we are checking if the message exists in database
             * In this case, no duplicate messages will be created
             */
            $folder->setMeta(static::DELTA_META_KEY, $newDeltaLink);
        }

        return $data;
    }

    /**
     * Handle any removed messages via delta.
     *
     * Removed messages can exists only when fetching the data via deltaLink.
     */
    protected function checkForRemovedMessages(Collection $messages, EmailAccountFolder $folder): Collection
    {
        return $messages->filter(function ($message) use ($folder) {
            if ($message->isRemoved()) {
                $this->addMessageToDeleteQueue($message->getId(), $folder);

                return false;
            }

            return true;
        })->values();
    }

    /**
     * Check whether the sync is initial one, the check is performed based on the delta link.
     */
    protected function isFolderInitialSync(EmailAccountFolder $folder): bool
    {
        return is_null($folder->getMeta(static::DELTA_META_KEY));
    }
}
