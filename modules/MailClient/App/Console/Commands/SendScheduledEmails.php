<?php
 

namespace Modules\MailClient\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Support\Carbon;
use Modules\MailClient\App\Models\ScheduledEmail;

class SendScheduledEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailclient:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email messages scheduled to send later.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ScheduledEmail::query()
            ->where(function (Builder $query) {
                $query->dueForSend();
            })
            ->orWhere(function (Builder $query) {
                $query->retryable(Carbon::asAppTimezone());
            })
            ->with(['media', 'account.sentFolder', 'account.oAuthAccount'])
            ->get()
            ->each(fn (ScheduledEmail $message) => $message->markAsSending())
            ->each(function (ScheduledEmail $message) {
                $message->send();
            });
    }
}
