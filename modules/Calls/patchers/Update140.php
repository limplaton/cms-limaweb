<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('calls')
            ->migrateMorphs('Modules\\Calls\\Models\\Call', 'Modules\\Calls\\App\\Models\\Call')
            ->migrateWorkflowTriggers([
                'Modules\\Calls\\Workflow\\Triggers\\MissedIncomingCall' => 'Modules\Calls\App\Workflow\Triggers\MissedIncomingCall',
            ]);
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
