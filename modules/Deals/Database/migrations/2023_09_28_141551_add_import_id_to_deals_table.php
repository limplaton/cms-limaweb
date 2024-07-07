<?php
 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->after('web_form_id', function (Blueprint $table) {
                $table->foreignId('import_id')->nullable()->constrained('imports')->nullOnDelete();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->removeColumn('import_id');
        });
    }
};