<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_program_id')->nullable()->after('course');
            $table->foreign('academic_program_id')
                ->references('program_id')
                ->on('academic_programs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropForeign(['academic_program_id']);
            $table->dropColumn('academic_program_id');
        });
    }
};
