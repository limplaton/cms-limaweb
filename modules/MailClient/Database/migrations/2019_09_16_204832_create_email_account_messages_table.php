<?php
 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_account_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained('email_accounts');
            $table->string('remote_id')->index()->comment('Remote Message Identifier (uuid, id)');

            // https://stackoverflow.com/questions/30079128/maximum-internet-email-message-id-length
            if (DB::getDriverName() !== 'sqlite') {
                $table->string('message_id', 995)->fullText()->nullable()->comment('Internet Message ID');
            } else {
                $table->string('message_id', 995)->nullable()->comment('Internet Message ID');
            }

            $table->char('hash', 32)->index()->nullable();
            $table->string('subject')->index()->nullable();
            $table->mediumText('html_body')->nullable();
            $table->mediumText('text_body')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_read')->index()->default(true);
            $table->boolean('is_sent_via_app')->default(true);
            $table->integer('opens')->nullable();
            $table->datetime('opened_at')->nullable();
            $table->integer('clicks')->nullable();
            $table->datetime('clicked_at')->nullable();
            $table->dateTime('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('email_account_messages');
    }
};
