<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('activities')
            ->migrateMorphs('App\\Models\\Activity', 'Modules\\Activities\\Models\\Activity')
            ->migrateMorphs('App\\Models\\Calendar', 'Modules\\Activities\\Models\\Calendar')
            ->migrateMailableTemplates($this->getActivityMailableTemplatesMap())
            ->migrateNotifications($this->getActivityNotificationsMap())
            ->migrateDbLanguageKeys('activity')
            ->migrateLanguageFiles(['activity.php', 'calendar.php'])
            ->migrateWorkflowActions($this->getActivitiesWorkflowActionsMap())
            ->deleteConflictedFiles($this->getConflictedFiles());
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/Activity.php'));
    }

    protected function getActivitiesWorkflowActionsMap(): array
    {
        return [
            'App\\Workflows\\Actions\\CreateActivityAction' => 'Modules\Activities\Workflow\Actions\CreateActivityAction',

            'App\\Workflows\\Actions\\DeleteAssociatedActivities' => 'Modules\Activities\Workflow\Actions\DeleteAssociatedActivities',

            'App\\Workflows\\Actions\\MarkAssociatedActivitiesAsComplete' => 'Modules\Activities\Workflow\Actions\MarkAssociatedActivitiesAsComplete',
        ];
    }

    protected function getActivityMailableTemplatesMap(): array
    {
        return [
            'App\\Mail\\ActivityReminder' => 'Modules\Activities\Mail\ActivityReminder',
            'App\\Mail\\ContactAttendsToActivity' => 'Modules\Activities\Mail\ContactAttendsToActivity',
            'App\\Mail\\UserAssignedToActivity' => 'Modules\Activities\Mail\UserAssignedToActivity',
            'App\\Mail\\UserAttendsToActivity' => 'Modules\Activities\Mail\UserAttendsToActivity',
        ];
    }

    protected function getActivityNotificationsMap(): array
    {
        return [
            'App\\Notifications\\ActivityReminder' => 'Modules\Activities\Notifications\ActivityReminder',
            'App\\Notifications\\UserAssignedToActivity' => 'Modules\Activities\Notifications\UserAssignedToActivity',
            'App\\Notifications\\UserAttendsToActivity' => 'Modules\Activities\Notifications\UserAttendsToActivity',
        ];
    }

    protected function getConflictedFiles(): array
    {
        return [
            app_path('Resources/Activity'),

            app_path('Mail/ActivityReminder.php'),
            app_path('Mail/ContactAttendsToActivity.php'),
            app_path('Mail/UserAssignedToActivity.php'),
            app_path('Mail/UserAttendsToActivity.php'),

            app_path('Notifications/ActivityReminder.php'),
            app_path('Notifications/UserAssignedToActivity.php'),
            app_path('Notifications/UserAttendsToActivity.php'),

            app_path('Workflows/Actions/CreateActivityAction.php'),
            app_path('Workflows/Actions/DeleteAssociatedActivities.php'),
            app_path('Workflows/Actions/MarkAssociatedActivitiesAsComplete.php'),

            app_path('Models/Activity.php'),
            app_path('Models/ActivityType.php'),
            app_path('Models/Calendar.php'),
            app_path('Models/Guest.php'),
        ];
    }
};
