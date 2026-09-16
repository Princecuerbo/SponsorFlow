<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'Endorsed' to fixed_list_items.status enum (backend-compatible with MySQL and PostgreSQL)
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE fixed_list_items MODIFY COLUMN status ENUM('Pending','Verified','Eligible','Ineligible','Endorsed') NOT NULL DEFAULT 'Pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TYPE fixed_list_items_status_enum ADD VALUE IF NOT EXISTS 'Endorsed'");
        }

        Schema::table('fixed_list_items', function (Blueprint $table): void {
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete()->after('fixed_list_id');
            $table->boolean('is_manually_endorsed')->default(false)->after('status');
            $table->foreignId('endorsed_by_id')->nullable()->constrained('users')->nullOnDelete()->after('is_manually_endorsed');
            $table->timestamp('endorsed_at')->nullable()->after('endorsed_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_list_items', function (Blueprint $table): void {
            $table->dropForeign(['application_id']);
            $table->dropForeign(['endorsed_by_id']);
            $table->dropColumn(['application_id', 'is_manually_endorsed', 'endorsed_by_id', 'endorsed_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fixed_list_items MODIFY COLUMN status ENUM('Pending','Verified','Eligible','Ineligible') NOT NULL DEFAULT 'Pending'");
        }
    }
};
