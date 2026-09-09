<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'resubmission_notes')) {
                $table->text('resubmission_notes')->nullable()->after('rejection_reason');
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Safe constraint handling for PostgreSQL
            DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check;");
            DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status::text IN ('Pending', 'Verified', 'Approved', 'Rejected', 'Ongoing', 'Expired', 'Resubmission Requested'));");
        } else {
            Schema::table('applications', function (Blueprint $table) {
                $table->string('status')->default('Pending')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'resubmission_notes')) {
                $table->dropColumn('resubmission_notes');
            }
        });

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS applications_status_check;");
        }
    }
};