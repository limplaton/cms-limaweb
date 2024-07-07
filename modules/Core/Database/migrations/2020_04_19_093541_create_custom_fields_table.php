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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('resource_name');
            $table->string('field_type');
            $table->string('field_id');
            $table->string('label');
            $table->boolean('is_unique')->nullable();
            $table->timestamps();
            $table->unique(['resource_name', 'field_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
