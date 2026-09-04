<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropUnique('applications_student_program_unique');
            $table->index(
                ['student_profile_id', 'sponsorship_program_id', 'status'],
                'applications_student_program_status_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_student_program_status_index');
            $table->unique(
                ['student_profile_id', 'sponsorship_program_id'],
                'applications_student_program_unique',
            );
        });
    }
};