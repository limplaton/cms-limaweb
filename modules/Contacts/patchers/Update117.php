<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('contacts')
            ->migrateMorphs('App\\Models\\Company', 'Modules\\Contacts\\Models\\Company')
            ->migrateMorphs('App\\Models\\Contact', 'Modules\\Contacts\\Models\\Contact')
            ->migrateMailableTemplates([
                'App\\Mail\\UserAssignedToCompany' => 'Modules\Contacts\Mail\UserAssignedToCompany',
                'App\\Mail\\UserAssignedToContact' => 'Modules\Contacts\Mail\UserAssignedToContact',
            ])
            ->migrateNotifications([
                'App\\Notifications\\UserAssignedToCompany' => 'Modules\Contacts\Notifications\UserAssignedToCompany',
                'App\\Notifications\\UserAssignedToContact' => 'Modules\Contacts\Notifications\UserAssignedToContact',
            ])
            ->migrateDbLanguageKeys('company')
            ->migrateDbLanguageKeys('contact')
            ->migrateDbLanguageKeys('source')
            ->migrateDbLanguageKeys('industry')
            ->migrateLanguageFiles(['company.php', 'contact.php', 'source.php', 'industry.php'])
            ->migrateWorkflowTriggers([
                'App\\Workflows\\Triggers\\CompanyCreated' => 'Modules\Contacts\Workflow\Triggers\CompanyCreated',
                'App\\Workflows\\Triggers\\ContactCreated' => 'Modules\Contacts\Workflow\Triggers\ContactCreated',
            ])
            ->deleteConflictedFiles($this->getConflictedFiles());
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/Company.php'));
    }

    protected function getConflictedFiles(): array
    {
        return [
            app_path('Resources/Contact'),
            app_path('Resources/Company'),
            app_path('Resources/Source'),
            app_path('Resources/Industry.php'),

            app_path('Mail/UserAssignedToCompany.php'),
            app_path('Mail/UserAssignedToContact.php'),

            app_path('Notifications/UserAssignedToCompany.php'),
            app_path('Notifications/UserAssignedToContact.php'),

            app_path('Workflows/Triggers/CompanyCreated.php'),
            app_path('Workflows/Triggers/ContactCreated.php'),

            app_path('Models/Contact.php'),
            app_path('Models/Company.php'),
            app_path('Models/Industry.php'),
            app_path('Models/Phone.php'),
            app_path('Models/Source.php'),
        ];
    }
};
