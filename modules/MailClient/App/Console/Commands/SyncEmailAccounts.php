<?php
 

namespace Modules\MailClient\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Collection;
use Modules\Core\App\Support\Carbon;
use Modules\MailClient\App\Events\EmailAccountsSynchronized;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Synchronization\EmailAccountSynchronizationManager;

class SyncEmailAccounts extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailclient:sync
                        {--account= : Email account ID}
                        {--broadcast : Whether to broadcast events}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes email accounts.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Gathering email accounts to sync.');

        $accounts = $this->getAccounts();

        if ($accounts->isEmpty()) {
            $this->info('No accounts found for synchronization.');
        } else {
            $this->info(sprintf('Performing sync for %d email accounts.', $accounts->count()));
        }

        $this->sync($accounts);
    }

    /**
     * Sync the email accounts.
     */
    protected function sync(Collection $accounts): void
    {
        $synced = false;

        // When the "inital sync from" option "now" is selected and the sync runs for first time
        // and if nothing is synchronized the UI message that initial sync is not performed won't be removed
        // In this case, will make sure to broadcast so the accounts are refetched
        $hasInitialSync = false;

        foreach ($accounts as $account) {
            if (! $account->isInitialSyncPerformed()) {
                $hasInitialSync = true;
            }

            $this->info(sprintf('Starting synchronization for account %s.', $account->email));

            $synchronizer = EmailAccountSynchronizationManager::getSynchronizer($account)->setCommand($this);

            if ($synchronizer->perform()) {
                $synced = true;
            }

            $account->fill(['last_sync_at' => Carbon::now()])->save();
        }

        if ($this->option('broadcast')) {
            event(new EmailAccountsSynchronized($synced || $hasInitialSync));
        }
    }

    /**
     * Get the accounts that should be synced.
     */
    protected function getAccounts(): Collection
    {
        $accounts = EmailAccount::with(['oAuthAccount', 'folders', 'user'])
            ->syncable()
            ->orderBy('email')
            ->get();

        if ($this->option('account')) {
            $accounts = $accounts->filter(function (EmailAccount $account) {
                return (int) $account->id === (int) $this->option('account');
            })->values();
        }

        return $accounts;
    }
}
