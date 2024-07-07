<?php
 

use App\ToModuleMigrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('mailclient')
            ->migrateWorkflowActions([
                'App\\Workflows\\Actions\\SendEmailAction' => 'Modules\\MailClient\\Workflow\\Actions\\SendEmailAction',
            ]);

        foreach ([
            'App\\Innoclapps\\Mail\\Headers\\AddressHeader' => 'Modules\\Core\\Mail\\Headers\\AddressHeader',
            'App\\Innoclapps\\Mail\\Headers\\DateHeader' => 'Modules\\Core\\Mail\\Headers\\DateHeader',
            'App\\Innoclapps\\Mail\\Headers\\IdHeader' => 'Modules\\Core\\Mail\\Headers\\IdHeader',
            'App\\Innoclapps\\Mail\\Headers\\Header' => 'Modules\\Core\\Mail\\Headers\\Header',
        ] as $oldHeader => $newHeader) {
            DB::table('email_account_message_headers')->where('header_type', $oldHeader)->update([
                'header_type' => $newHeader,
            ]);
        }

        if ($this->missingMessageHeadersIdColumn()) {
            Schema::table('email_account_message_headers', function (Blueprint $table) {
                $table->id();
            });
        }
    }

    public function shouldRun(): bool
    {
        return DB::table('workflows')
            ->where('action_type', 'App\\Workflows\\Actions\\SendEmailAction')->count() > 0 ||
            $this->missingMessageHeadersIdColumn();
    }

    protected function missingMessageHeadersIdColumn(): bool
    {
        return ! Schema::hasColumn('email_account_message_headers', 'id');
    }
};
