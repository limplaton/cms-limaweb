<?php
 

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['web_form_id']);

            $table->foreign('web_form_id')
                ->references('id')
                ->on('web_forms')
                ->nullOnDelete();
        });

        settings(['_patch_deal_web_form_id_applied' => true]);
    }

    public function shouldRun(): bool
    {
        return ! settings('_patch_deal_web_form_id_applied');
    }
};
