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
        Schema::create('scheduled_emails', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->mediumText('html_body');
            $table->text('to');
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->string('type');
            $table->text('associations')->nullable();
            $table->string('status')->index();
            $table->dateTime('failed_at')->index()->nullable(); // the final fail date
            $table->text('fail_reason')->nullable();
            $table->dateTime('retry_after')->index()->nullable();
            $table->unsignedInteger('retries')->index()->default(0);
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('email_account_id')->nullable()->constrained('email_accounts');
            $table->foreignId('related_message_id')->nullable()->constrained('email_account_messages');  // for reply and forward
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('scheduled_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_emails');
    }
};
