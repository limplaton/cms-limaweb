<?php
 

use App\ToModuleMigrator;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('mailclient')
            ->migrateMorphs('Modules\\MailClient\\Models\\EmailAccount', 'Modules\\MailClient\\App\\Models\\EmailAccount')
            ->migrateMorphs('Modules\\MailClient\\Models\\EmailAccountFolder', 'Modules\\MailClient\\App\\Models\\EmailAccountFolder')
            ->migrateMorphs('Modules\\MailClient\\Models\\EmailAccountMessage', 'Modules\\MailClient\\App\\Models\\EmailAccountMessage')
            ->migrateMorphs('Modules\\MailClient\\Models\\EmailAccountMessageAddress', 'Modules\\MailClient\\App\\Models\\EmailAccountMessageAddress')
            ->migrateMorphs('Modules\\MailClient\\Models\\EmailAccountMessageFolder', 'Modules\\MailClient\\App\\Models\\EmailAccountMessageFolder')
            ->migrateMorphs('Modules\\MailClient\\Models\\EmailAccountMessageHeader', 'Modules\\MailClient\\App\\Models\\EmailAccountMessageHeader')
            ->migrateWorkflowActions([
                'Modules\\MailClient\\Workflow\\Actions\\SendEmailAction' => 'Modules\\MailClient\\App\\Workflow\\Actions\\SendEmailAction',
            ]);

        foreach ([
            'Modules\\Core\\App\\Support\\Mail\\Headers\\AddressHeader' => ['Modules\\Core\\Mail\\Headers\\AddressHeader', 'Modules\\Core\\Support\\Mail\\Headers\\AddressHeader'],
            'Modules\\Core\\App\\Support\\Mail\\Headers\\DateHeader' => ['Modules\\Core\\Mail\\Headers\\DateHeader', 'Modules\\Core\\Support\\Mail\\Headers\\DateHeader'],
            'Modules\\Core\\App\\Support\\Mail\\Headers\\IdHeader' => ['Modules\\Core\\Mail\\Headers\\IdHeader', 'Modules\\Core\\Support\\Mail\\Headers\\IdHeader'],
            'Modules\\Core\\App\\Support\\Mail\\Headers\\Header' => ['Modules\\Core\\Mail\\Headers\\Header', 'Modules\\Core\\Support\\Mail\\Headers\\Header'],
        ] as $newHeader => $oldHeaders) {
            DB::table('email_account_message_headers')->whereIn('header_type', $oldHeaders)->update([
                'header_type' => $newHeader,
            ]);
        }
    }

    public function shouldRun(): bool
    {
        return true;
    }
};
