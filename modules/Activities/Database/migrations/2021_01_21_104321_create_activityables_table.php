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
        Schema::create('activityables', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained('activities');
            $table->morphs('activityable');
            $table->primary(
                ['activity_id', 'activityable_id', 'activityable_type'],
                'activityable_primary'
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
        Schema::dropIfExists('activityables');
    }
};
