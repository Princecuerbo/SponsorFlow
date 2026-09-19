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
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('gpa_submitted');
        });

        Schema::table('fixed_lists', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_lists', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['gpa_submitted']);
            $table->dropSoftDeletes();
        });

        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};