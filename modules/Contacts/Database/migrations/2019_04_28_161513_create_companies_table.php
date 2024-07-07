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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');

            $table->foreignId('user_id')->nullable()->comment('Owner')->constrained('users');

            $table->dateTime('owner_assigned_date')->nullable();

            $table->foreignId('source_id')->nullable()->constrained('sources');

            $table->foreignId('industry_id')->nullable()->constrained('industries');

            $table->foreignId('parent_company_id')->nullable()->constrained('companies');

            $table->string('name')->index();

            $table->string('email')->index()->nullable();

            $table->string('domain')->index()->nullable();

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
            $table->index(['street', 'city', 'state', 'postal_code', 'country_id']);

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
        Schema::dropIfExists('companies');
    }
};
