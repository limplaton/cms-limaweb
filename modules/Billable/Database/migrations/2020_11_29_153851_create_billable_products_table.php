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
        Schema::create('billable_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 3)->default(0);
            $table->decimal('qty', 15, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('tax_rate', 15, 3)->default(0);
            $table->string('tax_label');
            $table->string('discount_type')->nullable();
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('amount', 15, 3)->index()->default(0);
            $table->decimal('amount_tax_exl', 15, 3)->index()->default(0);
            $table->text('note')->nullable();
            $table->integer('display_order')->index();
            $table->foreignId('billable_id')->constrained('billables');
            $table->foreignId('product_id')->nullable()->constrained('products');
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
        Schema::dropIfExists('billable_products');
    }
};
