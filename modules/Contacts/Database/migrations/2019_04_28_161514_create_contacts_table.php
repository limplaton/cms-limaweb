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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index()->nullable();

            $table->uuid('uuid');

            $table->foreignId('user_id')->nullable()->comment('Owner')->constrained('users');

            $table->dateTime('owner_assigned_date')->nullable();

            $table->foreignId('source_id')->nullable()->constrained('sources');

            $table->string('first_name');
            $table->string('last_name')->nullable();

            $table->string('job_title')->nullable();

            $table->string('avatar')->nullable();

            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();

            $table->unsignedInteger('country_id')->nullable();
            $table->foreign('country_id')
                ->references('id')
                ->on(\Config::get('countries.table_name'));

            $table->foreignId('created_by')->nullable()->constrained('users');

            $table->foreignId('next_activity_id')->nullable()->constrained('activities');
            $table->dateTime('next_activity_date')->nullable()->index();

            $table->index(['first_name', 'last_name']);

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
        Schema::dropIfExists('contacts');
    }
};
