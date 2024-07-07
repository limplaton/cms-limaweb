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
        Schema::create('email_account_messageables', function (Blueprint $table) {
            $table->foreignId('message_id')->constrained('email_account_messages');
            $table->morphs('messageable', 'email_account_messageables_index');
            $table->primary(
                ['message_id', 'messageable_id', 'messageable_type'],
                'messageable_primary'
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
        Schema::dropIfExists('email_account_messageables');
    }
};
