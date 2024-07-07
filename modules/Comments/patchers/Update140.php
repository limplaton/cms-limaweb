<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('comments')
            ->migrateMorphs('Modules\\Comments\\Models\\Comment', 'Modules\\Comments\\App\\Models\\Comment');
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
