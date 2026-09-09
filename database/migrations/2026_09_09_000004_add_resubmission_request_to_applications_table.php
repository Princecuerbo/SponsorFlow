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
        Schema::table('applications', function (Blueprint $table) {
            $table->text('resubmission_notes')->nullable()->after('rejection_reason');
            $table->enum('status', [
                'Pending',
                'Verified',
                'Approved',
                'Rejected',
                'Ongoing',
                'Expired',
                'Resubmission Requested',
            ])->default('Pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('resubmission_notes');
            $table->enum('status', [
                'Pending',
                'Verified',
                'Approved',
                'Rejected',
                'Ongoing',
                'Expired',
            ])->default('Pending')->change();
        });
    }
};