<?php
 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Billable\App\Enums\TaxType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billables', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tax_type')->default(TaxType::exclusive->value);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->morphs('billableable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('billables');
    }
};
