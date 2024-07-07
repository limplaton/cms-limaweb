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
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();

            $table->string('email');
            $table->string('calendar_id')->index();

            $table->foreignId('user_id')->constrained('users');

            $table->foreignId('activity_type_id')->constrained('activity_types');

            $table->text('activity_types');
            $table->text('data')->nullable();

            $table->unsignedBigInteger('access_token_id')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'calendar_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
