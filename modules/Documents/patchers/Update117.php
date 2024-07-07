<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('documents')
            ->migrateMorphs('App\\Models\\Document', 'Modules\\Documents\\Models\\Document')
            ->migrateMailableTemplates($this->getMailableTemplatesMap())
            ->migrateNotifications($this->getNotificationsMap())
            ->migrateDbLanguageKeys('document')
            ->migrateLanguageFiles(['document.php'])
            ->migrateWorkflowTriggers([
                'App\\Workflows\\Triggers\\DocumentStatusChanged' => 'Modules\Documents\Workflow\Triggers\DocumentStatusChanged',
            ])
            ->deleteConflictedFiles($this->getConflictedFiles());
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/Document.php'));
    }

    protected function getMailableTemplatesMap(): array
    {
        return [
            'App\\Mail\\DocumentAccepted' => 'Modules\Documents\Mail\DocumentAccepted',
            'App\\Mail\\DocumentViewed' => 'Modules\Documents\Mail\DocumentViewed',
            'App\\Mail\\SignerSignedDocument' => 'Modules\Documents\Mail\SignerSignedDocument',
            'App\\Mail\\UserAssignedToDocument' => 'Modules\Documents\Mail\UserAssignedToDocument',
        ];
    }

    protected function getNotificationsMap(): array
    {
        return [
            'App\\Notifications\\DocumentAccepted' => 'Modules\Documents\Notifications\DocumentAccepted',
            'App\\Notifications\\DocumentViewed' => 'Modules\Documents\Notifications\DocumentViewed',
            'App\\Notifications\\SignerSignedDocument' => 'Modules\Documents\Notifications\SignerSignedDocument',
            'App\\Notifications\\UserAssignedToDocument' => 'Modules\Documents\Notifications\UserAssignedToDocument',
        ];
    }

    protected function getConflictedFiles(): array
    {
        return [
            app_path('Resources/Document'),

            app_path('Mail/DocumentAccepted.php'),
            app_path('Mail/DocumentAcceptedThankYouMessage.php'),
            app_path('Mail/DocumentMailable.php'),
            app_path('Mail/DocumentSignedThankYouMessage.php'),
            app_path('Mail/DocumentViewed.php'),
            app_path('Mail/SendDocument.php'),
            app_path('Mail/SignerSignedDocument.php'),
            app_path('Mail/UserAssignedToDocument.php'),

            app_path('Notifications/DocumentAccepted.php'),
            app_path('Notifications/DocumentViewed.php'),
            app_path('Notifications/SignerSignedDocument.php'),
            app_path('Notifications/UserAssignedToDocument.php'),

            app_path('Workflows/Triggers/DocumentStatusChanged.php'),

            app_path('Models/Document.php'),
            app_path('Models/DocumentType.php'),
            app_path('Models/DocumentSigner.php'),
            app_path('Models/DocumentTemplate.php'),
        ];
    }
};
