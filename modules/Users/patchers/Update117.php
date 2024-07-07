<?php
 

use App\ToModuleMigrator;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('users')
            ->migrateMorphs('App\\Models\\User', 'Modules\\Users\\Models\\User')
            ->migrateMorphs('App\\Models\\Team', 'Modules\\Users\\Models\\Team')
            ->migrateDbLanguageKeys('user')
            ->migrateMailableTemplates([
                'App\\Mail\\ResetPassword' => 'Modules\Users\Mail\ResetPassword',
                'App\\Mail\\InvitationCreated' => 'Modules\Users\Mail\InvitationCreated',
                'App\\Mail\\UserMentioned' => 'Modules\Users\Mail\UserMentioned',
            ])
            ->migrateNotifications([
                'App\\Notifications\\ResetPassword' => 'Modules\Users\Notifications\ResetPassword',
                'App\\Notifications\\UserMentioned' => 'Modules\Users\Notifications\UserMentioned',
            ])
            ->migrateLanguageFiles(['profile.php', 'team.php', 'user.php'])
            ->deleteConflictedFiles($this->getConflictedFiles());

        DB::table('notifications')->where('data', 'like', '%"key":"notifications.user_mentioned%')->update([
            'data' => DB::raw("REPLACE(data,'\"key\":\"notifications.user_mentioned','\"key\":\"users::user.notifications.user_mentioned')"),
        ]);
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/User.php'));
    }

    protected function getConflictedFiles(): array
    {
        return [
            app_path('Resources/User'),
            app_path('Models/User.php'),
        ];
    }
};
