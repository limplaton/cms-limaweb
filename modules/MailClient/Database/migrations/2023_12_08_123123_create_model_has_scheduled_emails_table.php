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
        Schema::create('model_has_scheduled_emails', function (Blueprint $table) {
            $table->foreignId('scheduled_email_id')->constrained('scheduled_emails')->cascadeOnDelete();

            $table->string('model_type');
            $table->string('model_id');

            $table->primary(['scheduled_email_id', 'model_id',  'model_type'], 'model_has_scheduled_emails_email_model_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_scheduled_emails');
    }
};
