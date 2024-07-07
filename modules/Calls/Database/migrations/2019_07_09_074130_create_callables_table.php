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
        Schema::create('callables', function (Blueprint $table) {
            $table->foreignId('call_id')->constrained('calls');
            $table->morphs('callable');
            $table->primary(
                ['call_id', 'callable_id', 'callable_type'],
                'callable_primary'
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
        Schema::dropIfExists('callables');
    }
};
