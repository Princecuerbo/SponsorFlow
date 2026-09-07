<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nullify any municipality strings incorrectly stored in the barangay column
        DB::table('student_profiles')
            ->whereIn('barangay', [
                'Mati City',
                'Baganga',
                'Banaybanay',
                'Boston',
                'Caraga',
                'Cateel',
                'Governor Generoso',
                'Lupon',
                'Manay',
                'San Isidro',
                'Tarragona',
            ])
            ->update(['barangay' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Fallback: Restore barangay values from municipality for records where barangay is null
        DB::table('student_profiles')
            ->whereNull('barangay')
            ->whereNotNull('municipality')
            ->update([
                'barangay' => DB::raw('municipality'),
            ]);
    }
};