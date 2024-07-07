<?php
 

namespace Modules\Core\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearExcelTmpPathCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'excel:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear excel temporary files.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem): void
    {
        $filesystem->deepCleanDirectory(
            $this->laravel['config']->get('excel.temporary_files.local_path')
        );

        $this->info('Excel temporary files were cleared.');
    }
}
