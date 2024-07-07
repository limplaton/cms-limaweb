<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('billable')
            ->migrateLanguageFiles(['billable.php', 'product.php'])
            ->deleteConflictedFiles([
                app_path('Resources/Product'), app_path('Models/Product.php'),
            ]);
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/Product.php'));
    }
};
