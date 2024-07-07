<?php
 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Deals\App\Enums\DealStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->uuid('uuid');
            $table->string('swatch_color', 7)->nullable();
            $table->foreignId('pipeline_id')->constrained('pipelines');
            $table->foreignId('stage_id')->constrained('stages');
            $table->unsignedInteger('status')->index()->default(DealStatus::open->value);
            $table->dateTime('won_date')->index()->nullable();
            $table->dateTime('lost_date')->index()->nullable();
            $table->string('lost_reason')->nullable()->index();
            $table->foreignId('user_id')->nullable()->comment('Owner')->constrained('users');
            $table->dateTime('owner_assigned_date')->nullable();
            $table->date('expected_close_date')->index()->nullable();
            $table->dateTime('stage_changed_date')->nullable();
            $table->decimal('amount', 15, 3)->index()->nullable();
            // Pushes new deals on board top when sorting by board_order
            $table->unsignedInteger('board_order')->index()->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('web_form_id')->nullable()->constrained('web_forms')->nullOnDelete();
            $table->foreignId('next_activity_id')->nullable()->constrained('activities');
            $table->dateTime('next_activity_date')->nullable()->index();
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
        Schema::dropIfExists('deals');
    }
};
