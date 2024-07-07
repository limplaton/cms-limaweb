<?php
 

namespace Modules\MailClient\App\Synchronization;

use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Modules\Core\App\Common\OAuth\EmptyRefreshTokenException;
use Modules\MailClient\App\Client\Contracts\FolderInterface;
use Modules\MailClient\App\Client\Contracts\ImapInterface;
use Modules\MailClient\App\Client\Contracts\MessageInterface;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Client\Exceptions\RateLimitExceededException;
use Modules\MailClient\App\Client\Exceptions\ServiceUnavailableException;
use Modules\MailClient\App\Client\FolderCollection;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Modules\MailClient\App\Services\EmailAccountMessageService;
use Modules\MailClient\App\Services\EmailAccountMessageSyncService;
use Throwable;

abstract class EmailAccountSynchronization extends EmailAccountSynchronizationManager
{
    use HandlesSynchronizationErrors, QueuesMessagesForDelete;

    /**
     * Determines how often "Processed X of N emails" hint should be added to a log.
     */
    const READ_HINT_COUNT = 100;

    /**
     * Determines how many emails can be stored in a database at once.
     */
    const DB_BATCH_SIZE = 100;

    /**
     * The IMAP client instance.
     */
    protected ?ImapInterface $imap = null;

    /**
     * @var \Modules\MailClient\App\Client\FolderCollection
     */
    protected $remoteFolders;

    /**
     * Indicates the minutes the folders should be synced.
     */
    protected int $syncFoldersEvery = 60;

    /**
     * Indicates whether changed were performed.
     */
    protected bool $synced = false;

    /**
     * Initialize new EmailAccountSynchronization instance.
     */
    public function __construct(
        protected EmailAccount $account,
        protected EmailAccountMessageSyncService $syncService,
        protected EmailAccountMessageService $service
    ) {
    }

    /**
     * Start account messages synchronization.
     *
     * @throws \Modules\MailClient\App\Synchronization\Exceptions\SyncFolderTimeoutException
     */
    abstract public function syncMessages(): void;

    /**
     * Start account folders synchronization.
     */
    abstract public function syncFolders(): void;

    /**
     * Perform account folders and messages synchronization.
     */
    public function perform(): bool
    {
        DB::disableQueryLog();

        $this->processStartTime = time();

        // Ensure all commonly used relations are loaded.
        $this->account = $this->account->load(
            ['folders.account', 'sentFolder.account', 'trashFolder.account']
        );

        if ($this->account->isSyncOnHold()) {
            $this->error(
                sprintf(
                    'Skipping sync for account %s, rescheduled for %s, account rate limit exceeded or service unavailable.',
                    $this->account->email,
                    $this->account->getSyncResumeDate()->toISOString(),
                )
            );

            return false;
        }

        try {
            $this->syncAccount();

            return $this->performed();
        } catch (ConnectionErrorException $e) {
            $this->handleConnectionErrorException($e);
        } catch (DecryptException) {
            $this->handleDecryptException();
        } catch (EmptyRefreshTokenException) {
            $this->handleEmptyRefreshTokenException();
        } catch (IdentityProviderException $e) {
            $this->handleIdentityProviderException($e);
        } catch (RateLimitExceededException|ServiceUnavailableException $e) {
            $this->handleRateLimitExceededOrServiceUnavilableException($e);
        } catch (Exception|Throwable $e) {
            // Catch any exception to prevent stopping account sync for other valid accounts.
            Log::error(
                "An error occured while synchronizing the {$this->account->email} email account. [{$e->getMessage()}]"
            );

            if (! app()->isProduction() || config('app.debug')) {
                throw $e;
            }
        }

        DB::enableQueryLog();

        return $this->performed();
    }

    /**
     * Sync the account.
     */
    protected function syncAccount(): void
    {
        // Folders must by synchronized first
        if ($this->shouldSyncFolders()) {
            $this->info('Starting syncing account folders.');
            $this->syncFolders();
            $this->account->setMeta('last-folders-sync', time());
        }

        // Messages should be synced last
        $this->info('Starting syncing account messages.');
        $this->syncMessages();

        // At the end, delete queued messages (if any)
        $this->info('Deleting messages queued for deletition.');
        $this->deleteQueuedMessages();

        $this->info(sprintf(
            'Synchronization for %s performed successfully.',
            $this->account->email
        ));

        $this->resetAfterSuccessfulSync();
    }

    /**
     * Check whether synchronization class performed any synchronization.
     */
    public function performed(): bool
    {
        return $this->synced;
    }

    /**
     * Reset the state of an account that successfully finished synchronization.
     */
    protected function resetAfterSuccessfulSync(): void
    {
        // If previously the account required auth and now it's resolved, update the database
        if ($this->account->requires_auth) {
            $this->account->setAuthRequired(false);
        }

        if (! $this->account->isSyncOnHold()) {
            $this->account->continueSync();
        }

        $this->account->removeMeta('connection-fails');
    }

    /**
     * Check whether the folders should be synchronized.
     */
    protected function shouldSyncFolders(): bool
    {
        $lastFoldersSync = $this->account->getMeta('last-folders-sync');

        return ! $lastFoldersSync || (time() > ($lastFoldersSync + ($this->syncFoldersEvery * 60)));
    }

    /**
     * Get the account imap client.
     *
     * @return \Modules\MailClient\App\Client\Contracts\ImapInterface|\Modules\MailClient\App\Client\Gmail\ImapClient|\Modules\MailClient\App\Client\Outlook\ImapClient|\Modules\MailClient\App\Client\Imap\ImapClient
     */
    public function getImapClient(): ImapInterface
    {
        return $this->imap ??= $this->account->createClient()->getImap();
    }

    /**
     * Process the messages that should be saved.
     *
     * @param  \Illuminate\Support\Enumerable  $messages
     * @param  \Modules\MailClient\App\Models\EmailAccountFolder|null  $folder
     *
     * Pass the folder the messages belongs to in case, unique remote_id is needed
     * e.q. for IMAP account the remote_id may not be unique, as the remote_id
     * is unique per folder.
     */
    protected function processMessages($messages, ?EmailAccountFolder $folder = null): void
    {
        $count = 0;
        $processed = 0;
        $batch = [];
        $total = $messages->count();

        $this->info(sprintf('Processing %s messages.', $total));

        foreach ($this->sortMessagesForSync($messages) as $message) {
            $processed++;

            if ($processed % static::READ_HINT_COUNT === 0) {
                $this->info("Processed $processed of $total messages.");
            }

            $count++;
            $batch[] = $message;

            if ($count === static::DB_BATCH_SIZE) {
                $this->saveMessages($batch, $folder);
                $count = 0;
                $batch = [];
            }

            if ($this->isTimeout()) {
                break;
            }
        }

        // Show the last processed hint count
        if ($processed % static::READ_HINT_COUNT !== 0) {
            $this->info("Processed $processed of $total messages.");
        }

        // Save the last massages added to the batch in case of timeout
        // or the DB_BATCH_SIZE is bigger than the actual messages size
        if ($count > 0) {
            $this->saveMessages($batch, $folder);
        }

        $this->cleanUpAfterFolderSyncComplete($folder);
    }

    /**
     * Save messages in database.
     *
     * @param  array  $messages
     */
    protected function saveMessages($messages, ?EmailAccountFolder $folder = null): void
    {
        foreach ($messages as $message) {
            // Check if message exists in database
            // If exists, we will perform update to this message
            if ($dbMessage = $this->findDatabaseMessageViaRemoteId($message->getId(), $folder)) {
                $this->updateMessage($message, $dbMessage);
            } else {
                // UPDATE: This can be solved with using the prefer header idtype immutableid
                // see https://learn.microsoft.com/en-us/graph/outlook-immutable-id
                // but we need to write migration script for the previous messages that were created with mutable ID
                // see https://learn.microsoft.com/en-us/graph/outlook-immutable-id#updating-existing-data

                // The message is deleted from the folder but maybe is moved to another folder
                // When moving messages from the application UI, this is handled
                // but when moving messages from outlook UI, we need to determine
                // if it's the same message and update in local database the new remote_id

                // NOTE: if the folder is not active, the messages from the synced and will be removed from the local database
                $queuedForRemoval = $this->getMessageFromDeleteQueue($message->getSubject(), $message->getMessageId());

                // If the message to be created exists in messages queued for removal then message is moved to another folder
                // in local database, we need to keep the same message with the updated values
                if ($queuedForRemoval) {
                    // Update local values with new values
                    $this->syncService->update($message, $queuedForRemoval->id);
                    $this->info(sprintf('Updated moved message, UID: %s', $message->getId()));
                    $this->synced = true;

                    // Unset as this message won't be deleted from database
                    // because already is updated with the new ID and folders
                    $this->removeMessageFromDeleteQueue($queuedForRemoval->subject, $queuedForRemoval->message_id);
                } else {
                    $this->syncService->create($this->account->id, $message);
                    $this->synced = true;
                }
            }
        }

        $this->cleanUp();
    }

    /**
     * Find database message via the message remote id.
     */
    protected function findDatabaseMessageViaRemoteId(string|int $remoteId, ?EmailAccountFolder $folder = null): ?EmailAccountMessage
    {
        return EmailAccountMessage::query()
            ->when($folder instanceof EmailAccountFolder,
                fn ($query) => $query->ofFolder($folder->id),
                fn ($query) => $query->where('email_account_id', $this->account->id)
            )
            ->where('remote_id', $remoteId)
            ->first();
    }

    /**
     * Delete the given message from storage.
     */
    protected function deleteMessage(string|int $id, ?EmailAccountFolder $folder = null): bool
    {
        if ($dbMessage = $this->findDatabaseMessageViaRemoteId($id, $folder)) {
            // Triggers observers
            $dbMessage->delete();

            $this->info(sprintf('Removed local message which was remotely removed, UID: %s', $id));
            $this->synced = true;

            return true;
        }

        return false;
    }

    /**
     * Update the given message in storage.
     */
    protected function updateMessage(MessageInterface $message, EmailAccountMessage $dbMessage): bool
    {
        // Message updated, update it in our local database too
        // e.q. can be used for draft updates messages and also
        // update properties like isRead etc...
        $updatedMessage = $this->syncService->update($message, $dbMessage);

        if ($updatedMessage->isDirty()) {
            $this->info(sprintf('Updated message, UID: %s', $updatedMessage->remote_id));
            $this->synced = true;

            return true;
        }

        return false;
    }

    /**
     * Get the account remote folders.
     */
    protected function getFolders(): FolderCollection
    {
        return $this->remoteFolders ??= $this->getImapClient()->getFolders();
    }

    /**
     * Find remote folder by a given database folder.
     *
     * @return \Modules\MailClient\App\Client\Contracts\FolderInterface|\Modules\MailClient\App\Client\Gmail\Folder|\Modules\MailClient\App\Client\Outlook\Folder|\Modules\MailClient\App\Client\Imap\Folder|null
     */
    protected function findFolder(EmailAccountFolder $folder): ?FolderInterface
    {
        return $this->getFolders()->find($folder->identifier());
    }

    /**
     * Find a database folder of a given remote folder.
     */
    protected function findDatabaseFolder(FolderInterface $remoteFolder): ?EmailAccountFolder
    {
        return $this->account->folders->findByIdentifier($remoteFolder->identifier());
    }

    /**
     * Remove remotely removed folders.
     */
    protected function removeRemotelyRemovedFolders(): void
    {
        $this->checkRemovedFolders(fn ($folder) => is_null($this->findFolder($folder)));
    }

    /**
     * Check remotely removed folders.
     *
     * Common function for all sync managers because the same logic is used
     * on all synchronization classes, the class just needs to provide a callback
     * whether to delete the folder.
     *
     * @param  \Closure  $deleteCallback  Provide a callback whether to delete the folder or not
     */
    protected function checkRemovedFolders(\Closure $deleteCallback): void
    {
        $this->info('Starting synchronization of remotely removed folders.');

        foreach ($this->account->folders as $databaseFolder) {
            if ($deleteCallback($databaseFolder) === true) {
                $this->deleteFolder($databaseFolder);
            }
        }
    }

    /**
     * Delete folder from database.
     */
    protected function deleteFolder(EmailAccountFolder $folder): void
    {
        $folder->delete();

        // Remove the account folder once it's removed from database
        // so it doesn't interfere with the synchronization e.q. trying
        // to sync a folder which is removed
        $newFoldersRelation = $this->account->folders->reject(
            fn (EmailAccountFolder $dbFolder) => $dbFolder->id === $folder->id
        )->values();

        $this->account->setRelation('folders', $newFoldersRelation);

        $this->info(sprintf(
            'Removed remotely deleted folder from local database, folder name: %s',
            $folder->name
        ));

        $this->synced = true;
    }

    /**
     * The messages must be synchronized from oldest to newest
     * as we can associate the replies associations and
     * possibility in future, create threads.
     */
    protected function sortMessagesForSync(Enumerable $messages): Enumerable
    {
        return $messages->sortBy(fn (MessageInterface $message) => $message->getDate())->values();
    }
}
