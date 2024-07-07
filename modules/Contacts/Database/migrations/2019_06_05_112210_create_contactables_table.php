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
        Schema::create('contactables', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained('contacts');
            $table->morphs('contactable');
            $table->timestamps();
            $table->primary(
                ['contact_id', 'contactable_id', 'contactable_type'],
                'contactable_primary'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists('contactables');
    }
};
