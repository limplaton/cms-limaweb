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
        Schema::create('web_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('status');
            $table->uuid('uuid');
            $table->string('title_prefix')->nullable();
            $table->text('sections');
            $table->text('notifications')->nullable();
            $table->text('styles');
            $table->text('submit_data');
            $table->unsignedInteger('total_submissions')->default(0);
            $table->string('locale');
            $table->foreignId('user_id')->comment('Owner')->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
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
        Schema::dropIfExists('web_forms');
    }
};
