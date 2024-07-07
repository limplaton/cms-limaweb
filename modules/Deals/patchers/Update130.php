<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingNextActivityDateTableColumn()) {
            Schema::table('deals', function (Blueprint $table) {
                $table->after('next_activity_id', function (Blueprint $table) {
                    $table->dateTime('next_activity_date')->nullable()->index();
                });
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingNextActivityDateTableColumn();
    }

    protected function missingNextActivityDateTableColumn(): bool
    {
        return ! Schema::hasColumn('deals', 'next_activity_date');
    }
};
