<?php
 

namespace Modules\Documents\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Documents\App\Models\Document;

class SendScheduledDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the documents which are scheduled for sending.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Document::dueForSending()
            ->get()
            ->each(function (Document $document) {
                try {
                    $document->send();
                } finally {
                    $document->fill(['send_at' => null])->save();
                }
            });
    }
}
