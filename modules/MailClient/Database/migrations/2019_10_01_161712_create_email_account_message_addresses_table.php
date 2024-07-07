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
        Schema::create('email_account_message_addresses', function (Blueprint $table) {
            $table->foreignId('message_id')->constrained('email_account_messages')->cascadeOnDelete();
            $table->string('address')->index()->nullable(); // For drafts without address
            $table->string('name')->index()->nullable();
            $table->string('address_type')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('email_account_message_addresses');
    }
};
