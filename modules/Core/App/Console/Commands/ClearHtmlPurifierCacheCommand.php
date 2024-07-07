<?php
 

namespace Modules\Core\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearHtmlPurifierCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'html-purifier:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the HTML Purifier cache.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem): void
    {
        $filesystem->deepCleanDirectory(
            $this->laravel['config']->get('html_purifier.cachePath')
        );

        $this->info('HTML Purifier cache has been flushed.');
    }
}
