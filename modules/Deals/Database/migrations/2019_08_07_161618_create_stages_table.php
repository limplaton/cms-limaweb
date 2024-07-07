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
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('win_probability')->default(100);
            $table->unsignedInteger('display_order')->index()->nullable();
            $table->foreignId('pipeline_id')->constrained('pipelines');
            $table->timestamps();
            $table->unique(['name', 'pipeline_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
