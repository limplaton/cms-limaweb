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
        Schema::create('dealables', function (Blueprint $table) {
            $table->foreignId('deal_id')->constrained('deals');
            $table->morphs('dealable');
            $table->timestamps();
            $table->primary(
                ['deal_id', 'dealable_id', 'dealable_type'],
                'dealable_primary'
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
        Schema::dropIfExists('dealables');
    }
};
