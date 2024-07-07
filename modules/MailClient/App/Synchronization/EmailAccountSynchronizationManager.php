<?php
 

namespace Modules\MailClient\App\Synchronization;

use Modules\MailClient\App\Console\Commands\SyncEmailAccounts;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Models\EmailAccountFolder;
use Modules\MailClient\App\Synchronization\Exceptions\SyncFolderTimeoutException;

abstract class EmailAccountSynchronizationManager
{
    /**
     * Time limit to sync account in seconds.
     */
    const MAX_ACCOUNT_SYNC_TIME = 240;

    /**
     * Max time in seconds between saved DB batches.
     */
    const DB_BATCH_TIME = 60;

    /**
     * Force mode indicator.
     */
    const FORCE_MODE = 'force';

    /**
     * Chill mode indicator.
     */
    const CHILL_MODE = 'chill';

    /**
     * Number of seconds passed to store last emails batch.
     */
    protected int $batchSaveTime = -1;

    /**
     * Timestamp when last batch was saved.
     */
    protected int $batchSaveTimestamp = 0;

    /**
     * Mode for the sync process.
     *
     * @var string chill|force
     */
    protected string $mode = self::CHILL_MODE;

    protected ?SyncEmailAccounts $command = null;

    protected int $processStartTime;

    /**
     * Get the synchronizer class.
     */
    public static function getSynchronizer(EmailAccount $account): EmailAccountSynchronization
    {
        $part = $account->connection_type->value;

        return self::{'get'.$part.'Synchronizer'}($account);
    }

    /**
     * Get the IMAP account synchronizer.
     */
    public static function getImapSynchronizer(EmailAccount $account): ImapEmailAccountSynchronization
    {
        return resolve(ImapEmailAccountSynchronization::class, ['account' => $account]);
    }

    /**
     * Get the Gmail account synchronizer.
     */
    public static function getGmailSynchronizer(EmailAccount $account): GmailEmailAccountSynchronization
    {
        return resolve(GmailEmailAccountSynchronization::class, ['account' => $account]);
    }

    /**
     * Get the Outlook account synchronizer.
     */
    public static function getOutlookSynchronizer(EmailAccount $account): OutlookEmailAccountSynchronization
    {
        return resolve(OutlookEmailAccountSynchronization::class, ['account' => $account]);
    }

    /**
     * Set the command class.
     */
    public function setCommand(SyncEmailAccounts $command): static
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Check whether the synchronization is in force mode.
     */
    public function isForceMode(): bool
    {
        return $this->mode === self::FORCE_MODE;
    }

    /**
     * Check whether the synchronization is in chill mode.
     */
    public function isChillMode(): bool
    {
        return $this->mode === self::CHILL_MODE;
    }

    /**
     * Check if sync timeout for the current account.
     *
     * Excluded in force mode.
     */
    protected function isTimeout(): bool
    {
        if ($this->isForceMode()) {
            return false;
        }

        return time() - $this->processStartTime > self::MAX_ACCOUNT_SYNC_TIME;
    }

    /**
     * Log info if process invoked via command.
     *
     * @param  string  $message
     */
    protected function info($message): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->info($message);
    }

    /**
     * Log error if process invoked via command.
     *
     * @param  string  $message
     */
    protected function error($message): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->error($message);
    }

    /**
     * Clean up after folder sync complete.
     */
    protected function cleanUpAfterFolderSyncComplete(?EmailAccountFolder $folder = null): void
    {
        $this->cleanUp(true, $folder);
    }

    /**
     * Tracks time when last batch was saved.
     *
     * Calculates time between batch saves.
     */
    protected function cleanUp(bool $isFolderSyncComplete = false, ?EmailAccountFolder $folder = null)
    {
        /**
         * In case folder sync completed and batch save time exceeded limit - throws exception.
         */
        if ($isFolderSyncComplete
            && $folder != null
            && $this->isChillMode()
            && $this->batchSaveTime > 0
            && $this->batchSaveTime > static::DB_BATCH_TIME
        ) {
            throw new SyncFolderTimeoutException($folder->account->email, $folder->name);
        } elseif ($isFolderSyncComplete) {
            /**
             * In case folder sync completed without batch save time exceed - reset batchSaveTime.
             */
            $this->batchSaveTime = -1;
        } else {
            /**
             * After batch save - calculate time difference between batches
             */
            if ($this->batchSaveTimestamp !== 0) {
                $this->batchSaveTime = time() - $this->batchSaveTimestamp;

                $this->info(sprintf('Batch save time: "%d" seconds.', $this->batchSaveTime));
            }
        }

        $this->batchSaveTimestamp = time();
    }
}
