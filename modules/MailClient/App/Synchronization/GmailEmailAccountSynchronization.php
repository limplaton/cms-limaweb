<?php
 

namespace Modules\MailClient\App\Synchronization;

use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Collection;
use Modules\Core\App\Common\Google\Services\MessageCollection;
use Modules\MailClient\App\Client\Gmail\Folder;
use Modules\MailClient\App\Models\EmailAccountFolder;

class GmailEmailAccountSynchronization extends EmailAccountIdBasedSynchronization
{
    /**
     * The history meta key.
     */
    const HISTORY_META_KEY = 'historyId';

    /**
     * Mode for the sync process.
     */
    protected string $mode = self::FORCE_MODE;

    /**
     * Limit the Gmail messages request.
     */
    protected int $limit = 150;

    /**
     * Start account messages synchronization.
     */
    public function syncMessages(): void
    {
        foreach ($this->account->activeFolders() as $folder) {
            if ($currentHistoryId = $folder->getMeta(static::HISTORY_META_KEY)) {
                $this->syncFromHistoryId($currentHistoryId, $folder);
            } else {
                $this->syncAll($folder);
            }
        }
    }

    /**
     * Sync account via Gmail history id data.
     *
     * @param  int  $currentHistoryId
     */
    protected function syncFromHistoryId($currentHistoryId, EmailAccountFolder $folder): void
    {
        $this->info(sprintf('Performing sync for folder %s via history id.', $folder->name));

        try {
            [
                'messages' => $messages,
                'newHistoryId' => $newHistoryId,
                'deleted' => $deletedMessages
            ] = $this->retrieveViaHistoryId($currentHistoryId, $folder);

            // Update/create for messages
            // Handles all three methods, messagesAded, labelsAdded, labelsRemoved
            $filtered = $messages->reject(
                fn ($history) => in_array($history->getMessage()->getId(), $deletedMessages)
            )
            // The messages may be duplicated multiple times in the Google history data
                ->unique(fn ($history) => $history->getMessage()->getId())
                ->map(fn ($history) => $history->getMessage())->values();

            // We will fetch each unique message via batch request so we can perform update or insert with the new data
            // The batch will also check for any messages which are not found and will remove them from the array
            $this->processMessages(
                $this->excludeSystemMailables($this->getImapClient()->batchGetMessages($filtered))
            );

            if (isset($newHistoryId)) {
                $folder->setMeta(static::HISTORY_META_KEY, $newHistoryId);
            }
        } catch (GoogleServiceException $e) {
            /*
            * A historyId is typically valid for at least a week, but in some rare circumstances
            * may be valid for only a few hours.
            *
            * If you receive an HTTP 404 error response, your application should perform a full sync.
            *
            * @link https://developers.google.com/gmail/api/v1/reference/users/history/list#startHistoryId
            */
            if ($e->getCode() == 404) {
                $this->error(sprintf(
                    'Folder %s history id (%s) not found, re-syncing all.',
                    $folder->name,
                    $currentHistoryId
                ));

                $this->syncAll($folder);

                return;
            }

            throw $e;
        }
    }

    /**
     * Sync all account messages.
     */
    protected function syncAll(EmailAccountFolder $folder): void
    {
        $remoteFolder = $this->findFolder($folder);

        // Trash and spam folders are not synced on the initial sync
        // But we need to get the first history id from the first message so
        // we can store the history id in database as it was synced
        if ($remoteFolder->isTrashOrSpam()) {
            $this->setFolderHistoryId(
                $folder,
                $this->getInitialMessages($remoteFolder, 1)->first()?->getHistoryId()
            );

            return;
        }

        $this->info(sprintf('Performing initial sync for folder %s.', $folder->name));

        $nextPageResult = null;
        $newHistoryId = null;

        // If _continue_sync_token flag is empty, will perform initial sync
        $continueFromPageToken = $folder->getMeta('_continue_sync_token');

        do {
            // Initial request
            if (! $nextPageResult) {
                $result = $this->getInitialMessages($remoteFolder);

                if (! is_null($continueFromPageToken)) {
                    $result->setNextPageToken($continueFromPageToken);
                    $result = $result->getNextPageResults();
                }
            } else {
                $result = $nextPageResult;
            }

            // Remember the first message as we will set the history id
            // after the messages are processed and the system mailables excluded
            // the message token will be saved after all data is saved.
            if ($result->isNotEmpty()) {
                // The supplied startHistoryId should be obtained from the historyId of a message, thread, or previous list response.
                $newHistoryId = $result->first()->getHistoryId();
            }

            $this->processMessages($this->excludeSystemMailables($result));
        } while ($nextPageResult = $result->getNextPageResults());

        $this->setFolderHistoryId($folder, $newHistoryId);

        $folder->removeMeta('_continue_sync_token');
    }

    /**
     * Retrieve data via history ID for the given folder.
     *
     * @param  int  $currentHistoryId
     */
    protected function retrieveViaHistoryId($historyId, EmailAccountFolder $folder): array
    {
        $nextPage = null;
        $deleted = [];
        $messages = collect([]);

        do {
            $historyList = $this->getImapClient()->getHistory($historyId, [
                'maxResults' => $this->limit,
                'pageToken' => $nextPage,
                'labelId' => $folder->remote_id,
            ]);

            foreach ($historyList->getHistory() ?? [] as $history) {
                // First handle all removed messages
                // Remove them from database so we can fetch all messages below in a batch and perform create/update
                foreach ($history->getMessagesDeleted() ?? [] as $message) {
                    $deleted[] = $messageId = $message->getMessage()->getId();

                    $this->deleteMessage($messageId);
                }

                $messages = $messages->merge($history->getMessagesAdded() ?? [])
                    ->merge($history->getLabelsAdded() ?? [])
                    ->merge($history->getLabelsRemoved() ?? []);
            }

            // We need to get the History ID in the first batch
            // so we can know up to which point the sync has been done for this user.
            if (! isset($newHistoryId)) {
                $newHistoryId = $historyList->getHistoryId();
            }
        } while (($nextPage = $historyList->getNextPageToken()));

        return [
            'newHistoryId' => $newHistoryId ?? null,
            'messages' => $messages,
            'deleted' => $deleted,
        ];
    }

    /**
     * Get the initial messages for the for sync.
     */
    protected function getInitialMessages(Folder $folder, ?int $limit = null): MessageCollection
    {
        return $folder->getMessagesFrom(
            $this->account->initial_sync_from->format('Y-m-d H:i:s'),
            $limit ?? $this->limit
        );
    }

    /**
     * We need to get the History ID from the very first existing message
     * so we can know up to which point the sync has been done for this folder.
     *
     * The the database folder history id from the provided message
     * In all cases, the provided message should be the first message.
     *
     * @param  string|null  $historyId
     */
    protected function setFolderHistoryId(EmailAccountFolder $folder, $historyId): void
    {
        if (is_null($historyId)) {
            return;
        }

        $folder->setMeta(static::HISTORY_META_KEY, $historyId);
    }

    /**
     * Exclude the mailables which are sent from the system notifications.
     */
    protected function excludeSystemMailables(Collection $messages): Collection
    {
        return $messages->filter(
            fn ($message) => is_null($message->getHeader('x--mailable'))
        )->values();
    }
}
