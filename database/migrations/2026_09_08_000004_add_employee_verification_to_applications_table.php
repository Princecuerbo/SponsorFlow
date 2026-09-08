<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('employee_name')->nullable()->after('is_rural_submitted');
            $table->string('employee_id_number')->nullable()->after('employee_name');
            $table->string('employee_relationship')->nullable()->after('employee_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['employee_name', 'employee_id_number', 'employee_relationship']);
        });
    }
};