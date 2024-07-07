<?php
 

use App\ToModuleMigrator;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('users')
            ->migrateMorphs('Modules\\Users\\Models\\User', 'Modules\\Users\\App\\Models\\User')
            ->migrateMorphs('Modules\\Users\\Models\\Team', 'Modules\\Users\\App\\Models\\Team')
            ->migrateMailableTemplates([
                'Modules\\Users\\Mail\\ResetPassword' => 'Modules\Users\App\Mail\ResetPassword',
                'Modules\\Users\\Mail\\InvitationCreated' => 'Modules\Users\App\Mail\InvitationCreated',
                'Modules\\Users\\Mail\\UserMentioned' => 'Modules\Users\App\Mail\UserMentioned',
            ])
            ->migrateNotifications([
                'Modules\\Users\\Notifications\\ResetPassword' => 'Modules\Users\App\Notifications\ResetPassword',
                'Modules\\Users\\Notifications\\UserMentioned' => 'Modules\Users\App\Notifications\UserMentioned',
            ]);

    }

    public function shouldRun(): bool
    {
        return true;
    }
};
