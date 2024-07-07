<?php
 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Common\Synchronization\SyncState;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('alias_email')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('connection_type');
            $table->unsignedBigInteger('access_token_id')->nullable();
            $table->unsignedBigInteger('sent_folder_id')->nullable();
            $table->unsignedBigInteger('trash_folder_id')->nullable();
            $table->boolean('create_contact')
                ->default(false)
                ->comment('Whether to create contact if the message sender does not exists.');
            $table->dateTime('initial_sync_from');
            $table->dateTime('last_sync_at')->nullable();
            $table->string('sync_state', 30)->default(SyncState::ENABLED->value);
            $table->text('sync_state_comment')->nullable();
            $table->boolean('requires_auth')->default(false);

            // IMAP
            $table->text('password')->nullable()->comment('IMAP');
            $table->boolean('validate_cert')->nullable()->comment('IMAP');
            $table->string('username')->nullable()->comment('IMAP');
            $table->string('imap_server')->nullable()->comment('IMAP');
            $table->unsignedInteger('imap_port')->nullable()->comment('IMAP');
            $table->string('imap_encryption', 8)->nullable()->comment('IMAP');
            $table->string('smtp_server')->nullable()->comment('IMAP');
            $table->unsignedInteger('smtp_port')->nullable()->comment('IMAP');
            $table->string('smtp_encryption', 8)->nullable()->comment('IMAP');

            $table->foreignId('created_by')->constrained('users');
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
        Schema::dropIfExists('email_accounts');
    }
};
