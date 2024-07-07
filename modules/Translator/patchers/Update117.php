<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('translator')->migrateLanguageFiles(['translator.php']);
    }

    public function shouldRun(): bool
    {
        return file_exists(lang_path('en/translator.php'));
    }
};
