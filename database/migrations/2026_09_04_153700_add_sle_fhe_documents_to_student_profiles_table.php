<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('sle_fhe_cg_path')->nullable()->after('is_sle_fhe_verified');
            $table->string('sle_fhe_residence_path')->nullable()->after('sle_fhe_cg_path');
            $table->string('sle_fhe_barangay_path')->nullable()->after('sle_fhe_residence_path');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['sle_fhe_cg_path', 'sle_fhe_residence_path', 'sle_fhe_barangay_path']);
        });
    }
};
