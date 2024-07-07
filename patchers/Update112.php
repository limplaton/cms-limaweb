<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingExpiresAtPersonalAccessTokensTableColumn()) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->after('last_used_at', function ($table) {
                    $table->timestamp('expires_at')->nullable();
                });
            });
        }

        if ($this->missingICalUidActivityCalendarSyncTableColumn()) {
            Schema::table('activity_calendar_sync', function (Blueprint $table) {
                $table->after('event_id', function ($table) {
                    $table->string('icaluid')->index();
                });

                $table->index('event_id');
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingExpiresAtPersonalAccessTokensTableColumn() ||
            $this->missingICalUidActivityCalendarSyncTableColumn();
    }

    protected function missingICalUidActivityCalendarSyncTableColumn(): bool
    {
        return ! Schema::hasColumn('activity_calendar_sync', 'icaluid');
    }

    protected function missingExpiresAtPersonalAccessTokensTableColumn(): bool
    {
        return ! Schema::hasColumn('personal_access_tokens', 'expires_at');
    }
};
