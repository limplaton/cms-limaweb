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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 3);
            $table->decimal('direct_cost', 15, 3)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('tax_rate', 15, 3)->default(0);
            $table->string('tax_label');
            $table->string('sku')->unique()->nullable();
            $table->boolean('is_active')->index()->default(true);
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
        Schema::dropIfExists('products');
    }
};
