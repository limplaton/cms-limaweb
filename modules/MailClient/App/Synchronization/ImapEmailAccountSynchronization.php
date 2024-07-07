<?php
 

namespace Modules\MailClient\App\Synchronization;

use Ddeboer\Imap\Exception\UnexpectedEncodingException;
use Ddeboer\Imap\Exception\UnsupportedCharsetException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Core\App\Common\Synchronization\SyncState;
use Modules\MailClient\App\Client\Imap\Folder;
use Modules\MailClient\App\Models\EmailAccountFolder;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Modules\MailClient\App\Synchronization\Exceptions\SyncFolderTimeoutException;

class ImapEmailAccountSynchronization extends EmailAccountSynchronization
{
    /**
     * Indiates the remote folder last uid.
     */
    const SKIPPED_LAST_UID_META_KEY = 'sync-skipped-last-uid';

    /**
     *  Maximum number of uidinvalidity errors in a row.
     */
    const MAX_UIDINVALID_RESYNCS = 5;

    /**
     * Start account messages synchronization.
     *
     * @throws \Modules\MailClient\App\Synchronization\Exceptions\SyncFolderTimeoutException
     */
    public function syncMessages(): void
    {
        $this->checkForRemovedMessages();

        foreach ($this->account->activeFolders() as $folder) {
            try {
                $this->retrieveAndProcess($folder);
            } catch (SyncFolderTimeoutException $e) {
                $this->error($e->getMessage());
            }

            if ($this->isTimeout()) {
                $this->error(sprintf('Synchronization interrupted by timeout after %s folder.', $folder->name));

                break;
            }
        }
    }

    /**
     * Retrieve and process the given folder messages.
     */
    protected function retrieveAndProcess(EmailAccountFolder $folder): void
    {
        $lastUid = $this->service->getLastUidByImapAccountByFolder($folder->id);

        if (! $lastUid) {
            // There is no lastuid, in this case, try to see if the folder
            // is skipped e.q. for spam and trash and sync the new messages from the stored last uid
            $lastUid = $folder->getMeta(self::SKIPPED_LAST_UID_META_KEY);
        }

        $remoteFolder = $this->findFolder($folder);

        if (! $this->checkSelectable($folder, $remoteFolder)) {
            return;
        }

        // Trash and spam folders are not synced on the initial sync,
        // we will store an indicator of the last uid for this folder
        // so for the next sync it syncs the actual new messages
        if (! $this->account->isInitialSyncPerformed() && $remoteFolder->isTrashOrSpam()) {
            $folder->setMeta(self::SKIPPED_LAST_UID_META_KEY, $remoteFolder->getLastUid());

            return;
        }

        if ($lastUid) {
            $this->info(sprintf('Gathering messages since last uid for folder %s.', $folder->name));

            // Delete the skipped last uid if exist
            if ($folder->hasMeta(self::SKIPPED_LAST_UID_META_KEY)) {
                $folder->removeMeta(self::SKIPPED_LAST_UID_META_KEY);
            }

            $messages = $remoteFolder->getMessagesSinceLastUid($lastUid);
        } else {
            $this->info(sprintf('Performing initial sync for folder %s.', $folder->name));

            $messages = $remoteFolder->getMessagesFrom($this->account->initial_sync_from->format('Y-m-d H:i:s'));
        }

        try {
            $this->processMessages($messages, $folder);
        } catch (UnexpectedEncodingException|UnsupportedCharsetException $e) {
            $this->error('Mail message was skipped from import because of '.Str::of($e::class)->headline()->lower().' exception.');
        }

        // Sync the flags only if it's not initial sync
        if ($lastUid) {
            $this->syncFlags($folder);
        }
    }

    /**
     * Sync the account folders.
     */
    public function syncFolders(): void
    {
        // The uid validity must be checked first
        $totalInvalidUidValidity = $this->checkFoldersByUidValidity();

        // Check that we're not stuck in an endless uidinvalidity resync loop.
        if ($totalInvalidUidValidity > self::MAX_UIDINVALID_RESYNCS) {
            $this->error('Resynced more than MAX_UIDINVALID_RESYNCS in a row. Stopping sync.');

            $this->account->setSyncState(
                SyncState::STOPPED,
                'Detected endless uidvalidity resync loop.'
            );

            return;
        }

        $this->removeRemotelyRemovedFolders();
    }

    /**
     * Synchronize message flags.
     */
    protected function syncFlags(EmailAccountFolder $folder): void
    {
        $this->info(sprintf('Starting syncing folder %s messages flags.', $folder->name));

        $remoteFolder = $this->findFolder($folder);

        if (! $this->checkSelectable($folder, $remoteFolder)) {
            return;
        }

        // Store the total read and unread before update so we can compare
        // them later after the update so we can know if sync is performed
        [$readCountBeforeUpdate, $unreadCountBeforeUpdate
        ] = [$folder->countReadMessages(), $folder->countUnreadMessages()];

        $this->updateReadAndUnreadMessages($remoteFolder, $folder->id);

        // Compare previous values with current values
        if ($readCountBeforeUpdate !== $folder->countReadMessages() ||
                $unreadCountBeforeUpdate !== $folder->countUnreadMessages()) {
            $this->synced = true;
        }
    }

    /**
     * Handle removed messages.
     */
    protected function checkForRemovedMessages(): void
    {
        foreach ($this->account->activeFolders() as $folder) {
            $remoteFolder = $this->findFolder($folder);

            if (! $this->checkSelectable($folder, $remoteFolder)) {
                continue;
            }

            $allFolderUids = $remoteFolder->getAllUids();

            foreach (EmailAccountMessage::ofFolder($folder->id)->lazy() as $message) {
                if (! $allFolderUids->contains($message->remote_id)) {
                    $this->addMessageToDeleteQueue($message->remote_id, $folder);
                }
            }
        }
    }

    /**
     * Check folders uidvalidity.
     *
     * @see  https://docs.nylas.com/docs/inconsistent-uidvalidity-value
     */
    protected function checkFoldersByUidValidity(): int
    {
        $this->info('Checking folders uidvalidity.');
        $totalInvalidUidValidity = 0;

        foreach ($this->account->folders as $databaseFolder) {
            $remoteFolder = $this->findFolder($databaseFolder);

            // Perhaps the folder is deleted? Will catch in the removeRemotelyRemovedFolders method.
            if (! $remoteFolder) {
                continue;
            }

            if ($remoteFolder->getId() != $databaseFolder->remote_id) {
                $totalInvalidUidValidity++;
                $this->info(sprintf('Found inconsistent uidvalidity for folder %s, clearing local cache', $databaseFolder->name));
                // Clear local cache, delete messages and all data
                // After that the folder will be re-created in the syncFolders method
                // On the next sync, the new messages will be fetched
                $this->deleteFolder($databaseFolder);
                $this->synced = true;
            }
        }

        return $totalInvalidUidValidity;
    }

    /**
     * Check the folder sync state.
     *
     * Useful when user injected selectable on non selectable folder via API.
     */
    protected function checkSelectable(EmailAccountFolder $folder, Folder $remoteFolder): bool
    {
        if (! $remoteFolder->isSelectable()) {
            $folder->markAsNotSelectable();

            return false;
        }

        return true;
    }

    /**
     * Update the read and unread messages for a given remote folder and local folder.
     */
    protected function updateReadAndUnreadMessages(Folder $remoteFolder, int $folderId): void
    {
        $since = $this->account->initial_sync_from->format('Y-m-d H:i:s');

        // TODO: Is chunking needed?

        $remoteFolder
            ->getSeenIds($since)
            ->chunk(500)
            ->each(function (Collection $ids) use ($folderId) {
                $this->service->markAsReadByRemoteIds($folderId, $ids->all());
            });

        $remoteFolder
            ->getUnseenIds($since)
            ->chunk(500)
            ->each(function (Collection $ids) use ($folderId) {
                $this->service->markAsUnreadByRemoteIds($folderId, $ids->all());
            });
    }
}
