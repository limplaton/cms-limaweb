<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingHashEmailAccountMessagesTableColumn()) {
            Schema::table('email_account_messages', function (Blueprint $table) {
                $table->after('message_id', function ($table) {
                    $table->char('hash', 32)->index()->nullable();
                });
            });
        }

        if ($this->missingOpensEmailAccountMessagesTableColumn()) {
            Schema::table('email_account_messages', function (Blueprint $table) {
                $table->after('is_sent_via_app', function ($table) {
                    $table->integer('opens')->nullable();
                });
            });
        }

        if ($this->missingOpenedAtEmailAccountMessagesTableColumn()) {
            Schema::table('email_account_messages', function (Blueprint $table) {
                $table->after('opens', function ($table) {
                    $table->datetime('opened_at')->nullable();
                });
            });
        }

        if ($this->missingClicksEmailAccountMessagesTableColumn()) {
            Schema::table('email_account_messages', function (Blueprint $table) {
                $table->after('opened_at', function ($table) {
                    $table->integer('clicks')->nullable();
                });
            });
        }

        if ($this->missingClickedAtEmailAccountMessagesTableColumn()) {
            Schema::table('email_account_messages', function (Blueprint $table) {
                $table->after('clicks', function ($table) {
                    $table->datetime('clicked_at')->nullable();
                });
            });
        }

        if ($this->missingSwatchColorCustomFieldOptionsTableColumn()) {
            Schema::table('custom_field_options', function (Blueprint $table) {
                $table->after('name', function ($table) {
                    $table->string('swatch_color', 7)->nullable();
                });
            });
        }

        if ($this->missingDisplayOrderCustomFieldOptionsTableColumn()) {
            Schema::table('custom_field_options', function (Blueprint $table) {
                $table->after('swatch_color', function ($table) {
                    $table->unsignedInteger('display_order')->index();
                });
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingHashEmailAccountMessagesTableColumn() ||
            $this->missingOpensEmailAccountMessagesTableColumn() ||
            $this->missingOpenedAtEmailAccountMessagesTableColumn() ||
            $this->missingClicksEmailAccountMessagesTableColumn() ||
            $this->missingClickedAtEmailAccountMessagesTableColumn() ||
            $this->missingSwatchColorCustomFieldOptionsTableColumn() ||
            $this->missingDisplayOrderCustomFieldOptionsTableColumn();
    }

    protected function missingHashEmailAccountMessagesTableColumn(): bool
    {
        return ! Schema::hasColumn('email_account_messages', 'hash');
    }

    protected function missingOpensEmailAccountMessagesTableColumn(): bool
    {
        return ! Schema::hasColumn('email_account_messages', 'opens');
    }

    protected function missingOpenedAtEmailAccountMessagesTableColumn(): bool
    {
        return ! Schema::hasColumn('email_account_messages', 'opened_at');
    }

    protected function missingClicksEmailAccountMessagesTableColumn(): bool
    {
        return ! Schema::hasColumn('email_account_messages', 'clicks');
    }

    protected function missingClickedAtEmailAccountMessagesTableColumn(): bool
    {
        return ! Schema::hasColumn('email_account_messages', 'clicked_at');
    }

    protected function missingSwatchColorCustomFieldOptionsTableColumn(): bool
    {
        return ! Schema::hasColumn('custom_field_options', 'swatch_color');
    }

    protected function missingDisplayOrderCustomFieldOptionsTableColumn(): bool
    {
        return ! Schema::hasColumn('custom_field_options', 'display_order');
    }
};
