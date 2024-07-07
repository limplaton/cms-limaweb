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
        Schema::create('noteables', function (Blueprint $table) {
            $table->foreignId('note_id')->constrained('notes');
            $table->morphs('noteable');
            $table->primary(
                ['note_id', 'noteable_id', 'noteable_type'],
                'noteable_primary'
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
        Schema::dropIfExists('noteables');
    }
};
