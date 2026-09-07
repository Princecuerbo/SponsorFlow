<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('student_id_number');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_name');
            $table->string('municipality', 100)->nullable()->after('birthdate');

            $table->index('municipality');
        });

        // Copy any legacy values from barangay into municipality using DB::raw update logic
        DB::table('student_profiles')
            ->whereNotNull('barangay')
            ->whereNull('municipality')
            ->update([
                'municipality' => DB::raw('barangay'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropIndex(['municipality']);
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'municipality',
            ]);
        });
    }
};
