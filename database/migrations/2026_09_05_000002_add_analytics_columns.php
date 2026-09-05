<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('year_level');
        });

        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->unsignedInteger('total_slots')->default(0)->after('available_slots');
        });

        DB::table('sponsorship_programs')->update([
            'total_slots' => DB::raw('available_slots'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->dropColumn('total_slots');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
