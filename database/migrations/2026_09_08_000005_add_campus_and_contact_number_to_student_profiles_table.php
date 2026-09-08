<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->enum('campus', [
                'Main Campus (City of Mati)',
                'Baganga Campus',
                'Banaybanay Campus',
                'Cateel Campus',
                'San Isidro Campus',
                'Tarragona Campus',
            ])->nullable()->after('course');
            $table->string('contact_number', 20)->nullable()->after('campus');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropColumn(['campus', 'contact_number']);
        });
    }
};