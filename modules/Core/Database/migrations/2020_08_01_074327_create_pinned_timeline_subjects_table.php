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
        Schema::create('pinned_timeline_subjects', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            // Using custom INDEX name
            // SQLSTATE[42000]: Syntax error or access violation: 1059 Identifier name 'tbl_pinned_timeline_subjects_timelineable_type_timelineable_id_index' is too long (SQL: alter table `tbl_pinned_timeline_subjects` add index `tbl_pinned_timeline_subjects_timelineable_type_timelineable_id_index`(`timelineable_type`, `timelineable_id`))
            $table->morphs('timelineable', 'timelineable_type_timelineable_id_index');
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
        Schema::dropIfExists('pinned_timeline_subjects');
    }
};
