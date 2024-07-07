<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('notes')
            ->migrateMorphs('Modules\\Notes\\Models\\Note', 'Modules\\Notes\\App\\Models\\Note');
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
