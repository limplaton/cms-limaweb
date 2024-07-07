<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingNextActivityDateTableColumn('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->after('next_activity_id', function (Blueprint $table) {
                    $table->dateTime('next_activity_date')->nullable()->index();
                });
            });
        }

        if ($this->missingNextActivityDateTableColumn('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->after('next_activity_id', function (Blueprint $table) {
                    $table->dateTime('next_activity_date')->nullable()->index();
                });
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingNextActivityDateTableColumn('contacts') ||
            $this->missingNextActivityDateTableColumn('companies');
    }

    protected function missingNextActivityDateTableColumn($table): bool
    {
        return ! Schema::hasColumn($table, 'next_activity_date');
    }
};
