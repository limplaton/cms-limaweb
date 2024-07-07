<?php
 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * Initialize new class instance.
     */
    public function __construct()
    {
        $this->tableName = \Config::get('settings.drivers.database.options.table', 'settings');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('key');
            $table->text('value')->nullable();
            // $table->timestamps();

            $table->unique(['user_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @codeCoverageIgnore
     */
    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
