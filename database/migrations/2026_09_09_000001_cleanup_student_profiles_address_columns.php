<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_profiles')
            ->whereNull('home_address')
            ->whereNotNull('address')
            ->update(['home_address' => DB::raw('address')]);

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->text('address')->nullable()->after('home_address');
        });
    }
};