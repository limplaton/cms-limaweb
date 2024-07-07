<?php
 

namespace Modules\MailClient\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\MailClient\App\Models\ScheduledEmail;

class PruneStaleScheduledEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailclient:prune-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale failed scheduled emails.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ScheduledEmail::with('media')
            ->orderByDesc('id')
            ->failed()
            ->whereNotNull('failed_at')
            ->where('failed_at', '<=', now()->subWeeks(2))
            ->get()
            ->each(function (ScheduledEmail $message) {
                $message->delete();
            });
    }
}
