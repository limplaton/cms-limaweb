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
        Schema::create('email_account_message_folders', function (Blueprint $table) {
            $table->foreignId('message_id')->constrained('email_account_messages')->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained('email_account_folders')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('email_account_message_folders');
    }
};
