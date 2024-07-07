<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($oldIndexName = $this->getEmailAccountMessagesTableMessageIdOldIndexName()) {
            Schema::table('email_account_messages', function (Blueprint $table) use ($oldIndexName) {
                $table->dropIndex($oldIndexName);
            });
        }

        if (is_null($this->getEmailAccountMessagesTableMessageIdNewIndexName())) {
            Schema::table('email_account_messages', function (Blueprint $table) {
                $table->string('message_id', 995)->fullText()->change();
            });
        }

        if (is_null($this->getContactsTableEmailIndexName())) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index('email');
            });
        }
    }

    public function shouldRun(): bool
    {
        return ! is_null($this->getEmailAccountMessagesTableMessageIdOldIndexName()) ||
            is_null($this->getEmailAccountMessagesTableMessageIdNewIndexName()) ||
            is_null($this->getContactsTableEmailIndexName());
    }

    protected function getEmailAccountMessagesTableMessageIdOldIndexName()
    {
        foreach ($this->getEmailAccountMessagesTableMessageIdIndexes() as $index) {
            if (str_ends_with($index['name'], 'message_id_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }

    protected function getEmailAccountMessagesTableMessageIdNewIndexName()
    {
        foreach ($this->getEmailAccountMessagesTableMessageIdIndexes() as $index) {
            if (str_ends_with($index['name'], 'message_id_fulltext') && $index['type'] == 'fulltext') {
                return $index['name'];
            }
        }
    }

    protected function getContactsTableEmailIndexName()
    {
        foreach ($this->getContactsTableEmailIndexes() as $index) {
            if (str_ends_with($index['name'], 'email_index') && $index['type'] == 'btree') {
                return $index['name'];
            }
        }
    }

    protected function getEmailAccountMessagesTableMessageIdIndexes()
    {
        return $this->getColumnIndexes('email_account_messages', 'message_id');
    }

    protected function getContactsTableEmailIndexes()
    {
        return $this->getColumnIndexes('contacts', 'email');
    }
};
