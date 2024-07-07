<?php
 

namespace Modules\Core\App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the application by caching bootstrap files.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Caching the application bootstrap files.');

        $this->call('optimize');
    }
}
