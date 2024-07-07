<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;
use Modules\Users\App\Models\User;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingTeamsUserIdTableColumn()) {
            Schema::disableForeignKeyConstraints();

            Schema::table('teams', function (Blueprint $table) {
                $table->after('description', function (Blueprint $table) {
                    $table->foreignId('user_id')->comment('Manager')->constrained('users');
                });
            });

            $admin = User::where('super_admin', 1)->orderBy('id')->first();

            \DB::table('teams')->update(['user_id' => $admin->id]);
        }

        if (! $this->getCompaniesTableEmailColumnIndexName()) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index('email');
            });
        }

        if (! $this->getCompaniesTableNameColumnIndexName()) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index('name');
            });
        }

        if (! $this->getCompaniesTableDomainColumnIndexName()) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index('domain');
            });
        }

        if ($this->missingIsUniqueCustomFieldsTableColumn()) {
            Schema::table('custom_fields', function (Blueprint $table) {
                $table->after('label', function ($table) {
                    $table->boolean('is_unique')->nullable();
                });
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingIsUniqueCustomFieldsTableColumn() ||
            $this->missingTeamsUserIdTableColumn() ||
            ! $this->getCompaniesTableEmailColumnIndexName() ||
            ! $this->getCompaniesTableNameColumnIndexName() ||
            ! $this->getCompaniesTableDomainColumnIndexName();
    }

    protected function missingIsUniqueCustomFieldsTableColumn(): bool
    {
        return ! Schema::hasColumn('custom_fields', 'is_unique');
    }

    protected function missingTeamsUserIdTableColumn(): bool
    {
        return ! Schema::hasColumn('teams', 'user_id');
    }

    protected function getCompaniesTableEmailColumnIndexName()
    {
        foreach ($this->getColumnIndexes('companies', 'email') as $index) {
            if (str_ends_with($index['name'], 'email_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }

    protected function getCompaniesTableDomainColumnIndexName()
    {
        foreach ($this->getColumnIndexes('companies', 'domain') as $index) {
            if (str_ends_with($index['name'], 'domain_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }

    protected function getCompaniesTableNameColumnIndexName()
    {
        foreach ($this->getColumnIndexes('companies', 'name') as $index) {
            if (str_ends_with($index['name'], 'name_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }
};
