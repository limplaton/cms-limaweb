<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('deals')
            ->migrateMorphs('Modules\\Deals\\Models\\Deal', 'Modules\\Deals\\App\\Models\\Deal')
            ->migrateMailableTemplates([
                'Modules\\Deals\\Mail\\UserAssignedToDeal' => 'Modules\Deals\App\Mail\UserAssignedToDeal',
            ])
            ->migrateNotifications([
                'Modules\\Deals\\Notifications\\UserAssignedToDeal' => 'Modules\Deals\App\Notifications\UserAssignedToDeal',
            ])
            ->migrateWorkflowTriggers([
                'Modules\\Deals\\Workflow\\Triggers\\DealCreated' => 'Modules\Deals\App\Workflow\Triggers\DealCreated',
                'Modules\\Deals\\Workflow\\Triggers\\DealStageChanged' => 'Modules\Deals\App\Workflow\Triggers\DealStageChanged',
                'Modules\\Deals\\Workflow\\Triggers\\DealStatusChanged' => 'Modules\Deals\App\Workflow\Triggers\DealStatusChanged',
            ])
            ->migrateWorkflowActions([
                'Modules\\Deals\\Workflow\\Triggers\\MarkAssociatedDealsAsLost' => 'Modules\Deals\App\Workflow\Triggers\MarkAssociatedDealsAsLost',
                'Modules\\Deals\\Workflow\\Triggers\\MarkAssociatedDealsAsWon' => 'Modules\Deals\App\Workflow\Triggers\MarkAssociatedDealsAsWon',
            ]);
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
