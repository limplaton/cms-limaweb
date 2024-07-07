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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();

            $table->text('description')->nullable();
            $table->text('note')->nullable();

            $table->date('due_date')->index();
            $table->time('due_time')->nullable()->index();

            $table->time('end_time')->nullable()->index();
            $table->date('end_date')->index();

            $table->unsignedInteger('reminder_minutes_before')->nullable();
            $table->dateTime('reminder_at')->index()->nullable();
            $table->dateTime('reminded_at')->nullable();

            $table->foreignId('user_id')->comment('Owner')->constrained('users');

            $table->dateTime('owner_assigned_date');

            $table->foreignId('activity_type_id')->constrained('activity_types');

            $table->dateTime('completed_at')->index()->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->index(['due_date', 'due_time']);

            $table->softDeletes();
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
        Schema::dropIfExists('activities');
    }
};
