<?php
 

namespace Modules\Core\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the application cache.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem): void
    {
        $filesystem->deepCleanDirectory(
            $this->laravel['config']->get('dompdf.options.font_cache')
        );

        $this->info('dompdf fonts cache cleared.');

        foreach ([
            'optimize:clear', 'html-purifier:clear', 'modelCache:clear',
        ] as $command) {
            $this->call($command);
        }
    }
}
