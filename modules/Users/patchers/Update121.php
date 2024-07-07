<?php
 

use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;
use Modules\Users\App\Models\User;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingNotificationsSettingsColumn()) {
            Schema::table('users', function ($table) {
                $table->after('avatar', function ($table) {
                    $table->text('notifications_settings')->nullable();
                });
            });

            User::get()->each(function ($user) {
                $user->notifications_settings = $user->getMeta('notification-settings') ?: [];
                $user->save();
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingNotificationsSettingsColumn();
    }

    protected function missingNotificationsSettingsColumn(): bool
    {
        return ! Schema::hasColumn('users', 'notifications_settings');
    }
};
