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
        Schema::create('message_links_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('email_account_messages')->cascadeOnDelete();
            $table->text('url')->nullable();
            $table->integer('total')->default(1);
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
        Schema::dropIfExists('message_link_clicks');
    }
};
