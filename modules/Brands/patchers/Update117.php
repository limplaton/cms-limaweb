<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('deals')
            ->migrateLanguageFiles(['brand.php'])
            ->deleteConflictedFiles($this->getConflictedFiles());
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/Brand.php'));
    }

    protected function getConflictedFiles(): array
    {
        return [
            app_path('Models/Brand.php'),
        ];
    }
};
