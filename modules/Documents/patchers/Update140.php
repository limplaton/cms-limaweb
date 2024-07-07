<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('documents')
            ->migrateMorphs('Modules\\Documents\\Models\\Document', 'Modules\\Documents\\App\\Models\\Document')
            ->migrateMailableTemplates($this->getMailableTemplatesMap())
            ->migrateNotifications($this->getNotificationsMap())
            ->migrateWorkflowTriggers([
                'Modules\\Documents\\Workflow\\Triggers\\DocumentStatusChanged' => 'Modules\Documents\App\Workflow\Triggers\DocumentStatusChanged',
            ]);
    }

    public function shouldRun(): bool
    {
        return true;
    }

    protected function getMailableTemplatesMap(): array
    {
        return [
            'Modules\\Documents\\Mail\\DocumentAccepted' => 'Modules\Documents\App\Mail\DocumentAccepted',
            'Modules\\Documents\\Mail\\DocumentViewed' => 'Modules\Documents\App\Mail\DocumentViewed',
            'Modules\\Documents\\Mail\\SignerSignedDocument' => 'Modules\Documents\App\Mail\SignerSignedDocument',
            'Modules\\Documents\\Mail\\UserAssignedToDocument' => 'Modules\Documents\App\Mail\UserAssignedToDocument',
        ];
    }

    protected function getNotificationsMap(): array
    {
        return [
            'Modules\\Documents\\Notifications\\DocumentAccepted' => 'Modules\Documents\App\Notifications\DocumentAccepted',
            'Modules\\Documents\\Notifications\\DocumentViewed' => 'Modules\Documents\App\Notifications\DocumentViewed',
            'Modules\\Documents\\Notifications\\SignerSignedDocument' => 'Modules\Documents\App\Notifications\SignerSignedDocument',
            'Modules\\Documents\\Notifications\\UserAssignedToDocument' => 'Modules\Documents\App\Notifications\UserAssignedToDocument',
        ];
    }
};
