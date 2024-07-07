<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('contacts')
            ->migrateMorphs('Modules\\Contacts\\Models\\Company', 'Modules\\Contacts\\App\\Models\\Company')
            ->migrateMorphs('Modules\\Contacts\\Models\\Contact', 'Modules\\Contacts\\App\\Models\\Contact')
            ->migrateMailableTemplates([
                'Modules\\Contacts\\Mail\\UserAssignedToCompany' => 'Modules\Contacts\App\Mail\UserAssignedToCompany',
                'Modules\\Contacts\\Mail\\UserAssignedToContact' => 'Modules\Contacts\App\Mail\UserAssignedToContact',
            ])
            ->migrateNotifications([
                'Modules\\Contacts\\Notifications\\UserAssignedToCompany' => 'Modules\Contacts\App\Notifications\UserAssignedToCompany',
                'Modules\\Contacts\\Notifications\\UserAssignedToContact' => 'Modules\Contacts\App\Notifications\UserAssignedToContact',
            ])
            ->migrateWorkflowTriggers([
                'Modules\\Contacts\\Workflow\\Triggers\\CompanyCreated' => 'Modules\Contacts\App\Workflow\Triggers\CompanyCreated',
                'Modules\\Contacts\\Workflow\\Triggers\\ContactCreated' => 'Modules\Contacts\App\Workflow\Triggers\ContactCreated',
            ]);
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
