<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('activities')
            ->migrateMorphs('Modules\\Activities\\Models\\Activity', 'Modules\\Activities\\App\\Models\\Activity')
            ->migrateMorphs('Modules\\Activities\\Models\\Calendar', 'Modules\\Activities\\App\\Models\\Calendar')
            ->migrateMailableTemplates($this->getActivityMailableTemplatesMap())
            ->migrateNotifications($this->getActivityNotificationsMap())
            ->migrateWorkflowActions($this->getActivitiesWorkflowActionsMap());
    }

    public function shouldRun(): bool
    {
        return true;
    }

    protected function getActivitiesWorkflowActionsMap(): array
    {
        return [
            'Modules\\Activities\\Workflow\\Actions\\CreateActivityAction' => 'Modules\Activities\App\Workflow\Actions\CreateActivityAction',

            'Modules\\Activities\\Workflow\\Actions\\DeleteAssociatedActivities' => 'Modules\Activities\App\Workflow\Actions\DeleteAssociatedActivities',

            'Modules\\Activities\\Workflow\\Actions\\MarkAssociatedActivitiesAsComplete' => 'Modules\Activities\App\Workflow\Actions\MarkAssociatedActivitiesAsComplete',
        ];
    }

    protected function getActivityMailableTemplatesMap(): array
    {
        return [
            'Modules\\Activities\\Mail\\ActivityReminder' => 'Modules\Activities\App\Mail\ActivityReminder',
            'Modules\\Activities\\Mail\\ContactAttendsToActivity' => 'Modules\Activities\App\Mail\ContactAttendsToActivity',
            'Modules\\Activities\\Mail\\UserAssignedToActivity' => 'Modules\Activities\App\Mail\UserAssignedToActivity',
            'Modules\\Activities\\Mail\\UserAttendsToActivity' => 'Modules\Activities\App\Mail\UserAttendsToActivity',
        ];
    }

    protected function getActivityNotificationsMap(): array
    {
        return [
            'Modules\\Activities\\Notifications\\ActivityReminder' => 'Modules\Activities\App\Notifications\ActivityReminder',
            'Modules\\Activities\\Notifications\\UserAssignedToActivity' => 'Modules\Activities\App\Notifications\UserAssignedToActivity',
            'Modules\\Activities\\Notifications\\UserAttendsToActivity' => 'Modules\Activities\App\Notifications\UserAttendsToActivity',
        ];
    }
};
