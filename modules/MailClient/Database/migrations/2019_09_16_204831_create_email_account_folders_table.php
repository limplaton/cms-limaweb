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
        Schema::create('email_account_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreignId('email_account_id')->constrained('email_accounts');
            $table->string('remote_id')->nullable()
                ->comment('API ID, uidvalidity etc...');
            $table->boolean('support_move')->default(true);
            $table->boolean('syncable')->index()->default(false);
            $table->boolean('selectable')->default(false);
            $table->string('type')->nullable();
            $table->string('name');
            $table->string('display_name');
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
        Schema::dropIfExists('email_account_folders');
    }
};
