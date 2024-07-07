<?php
 

namespace Modules\Core\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\App\Updater\UpdateFinalizer;

class FinalizeUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updater:finalize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finalize the application recent update.';

    /**
     * Execute the console command.
     */
    public function handle(UpdateFinalizer $finalizer): void
    {
        if (! $finalizer->needed()) {
            $this->info('There is no update to finalize.');
        } else {
            $finalizer->run();

            $this->info('The update has been finalized.');
        }
    }
}
