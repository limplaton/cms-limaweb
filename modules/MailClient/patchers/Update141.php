<?php
 

use Illuminate\Support\Facades\DB;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        foreach ([
            'Modules\\Core\\App\\Common\\Mail\\Headers\\AddressHeader' => 'Modules\\Core\\App\\Support\\Mail\\Headers\\AddressHeader',
            'Modules\\Core\\App\\Common\\Mail\\Headers\\DateHeader' => 'Modules\\Core\\App\\Support\\Mail\\Headers\\DateHeader',
            'Modules\\Core\\App\\Common\\Mail\\Headers\\IdHeader' => 'Modules\\Core\\App\\Support\\Mail\\Headers\\IdHeader',
            'Modules\\Core\\App\\Common\\Mail\\Headers\\Header' => 'Modules\\Core\\App\\Support\\Mail\\Headers\\Header',
        ] as $newHeader => $oldHeader) {
            DB::table('email_account_message_headers')->where('header_type', $oldHeader)->update([
                'header_type' => $newHeader,
            ]);
        }
    }

    public function shouldRun(): bool
    {
        return DB::table('email_account_message_headers')->where('header_type', 'like', '%Support%')->count() > 0;
    }
};
