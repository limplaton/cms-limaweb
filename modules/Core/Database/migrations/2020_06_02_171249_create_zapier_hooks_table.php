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
        Schema::create('zapier_hooks', function (Blueprint $table) {
            $table->id();
            $table->string('hook');
            $table->string('resource_name');
            $table->string('action');
            $table->text('data')->nullable();
            $table->unsignedBigInteger('zap_id')->index();
            $table->foreignId('user_id')->index()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('zapier_hooks');
    }
};
