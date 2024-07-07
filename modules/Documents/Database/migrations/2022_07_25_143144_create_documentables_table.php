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
        Schema::create('documentables', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('documents');
            $table->morphs('documentable');
            $table->timestamps();
            $table->primary(
                ['document_id', 'documentable_id', 'documentable_type'],
                'documentable_primary'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('documentables');
    }
};
