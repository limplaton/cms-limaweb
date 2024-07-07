<?php
 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Documents\App\Enums\DocumentViewType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->uuid('uuid');
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->decimal('amount', 15, 3)->index()->nullable();
            $table->string('status')->index();
            $table->boolean('requires_signature')->default(true);
            $table->mediumText('content')->nullable();
            $table->string('view_type')->default(DocumentViewType::NAV_TOP->value);
            $table->string('locale');
            $table->dateTime('accepted_at')->nullable();
            $table->foreignId('marked_accepted_by')->nullable()->constrained('users');
            $table->dateTime('send_at')->nullable();
            $table->dateTime('original_date_sent')->nullable();
            $table->dateTime('last_date_sent')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users');

            // Not yet implemented, only the columns exists
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_date')->nullable();
            $table->text('approval_feedback')->nullable();

            $table->foreignId('brand_id')->constrained('brands');
            $table->text('data')->nullable();
            $table->foreignId('user_id')->comment('Owner')->constrained('users');
            $table->dateTime('owner_assigned_date');
            $table->foreignId('created_by')->constrained('users');
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
        Schema::dropIfExists('documents');
    }
};
