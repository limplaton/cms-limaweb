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
        Schema::create('document_signers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->boolean('send_email');
            $table->dateTime('sent_at')->nullable();
            $table->string('signature')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->ipAddress('sign_ip')->nullable();
            $table->foreignId('document_id')->constrained('documents');
            $table->timestamps();
            $table->unique(['document_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('document_signers');
    }
};
