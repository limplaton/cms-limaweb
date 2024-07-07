<?php
 

namespace Modules\Translator\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Translator\App\Translator;

class GenerateJsonLanguageFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translator:json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate application json language file.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Translator::generateJsonLanguageFile();

        $this->info('Language file generated successfully.');
    }
}
