<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'Saved' to fixed_lists.status enum
        // MySQL: MODIFY COLUMN with expanded ENUM list
        // PostgreSQL: Laravel enum columns use CHECK constraints, not custom types.
        //   We drop the old constraint and add a new one with the expanded values.
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE fixed_lists MODIFY COLUMN status ENUM('Draft','Submitted','Approved','Rejected','Saved') NOT NULL DEFAULT 'Draft'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE fixed_lists DROP CONSTRAINT IF EXISTS fixed_lists_status_check");
            DB::statement("ALTER TABLE fixed_lists ADD CONSTRAINT fixed_lists_status_check CHECK (status::text = ANY (ARRAY['Draft'::character varying, 'Submitted'::character varying, 'Approved'::character varying, 'Rejected'::character varying, 'Saved'::character varying]::text[]))");
        }

        Schema::table('fixed_lists', function (Blueprint $table): void {
            $table->timestamp('fassg_assigned_at')->nullable()->after('total_names');
            $table->foreignId('fassg_assigned_by_id')->nullable()->constrained('users')->nullOnDelete()->after('fassg_assigned_at');
        });

        Schema::table('fixed_list_items', function (Blueprint $table): void {
            $table->timestamp('fassg_assigned_at')->nullable()->after('endorsed_at');
            $table->foreignId('fassg_assigned_by_id')->nullable()->constrained('users')->nullOnDelete()->after('fassg_assigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_list_items', function (Blueprint $table): void {
            $table->dropForeign(['fassg_assigned_by_id']);
            $table->dropColumn(['fassg_assigned_at', 'fassg_assigned_by_id']);
        });

        Schema::table('fixed_lists', function (Blueprint $table): void {
            $table->dropForeign(['fassg_assigned_by_id']);
            $table->dropColumn(['fassg_assigned_at', 'fassg_assigned_by_id']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE fixed_lists MODIFY COLUMN status ENUM('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE fixed_lists DROP CONSTRAINT IF EXISTS fixed_lists_status_check");
            DB::statement("ALTER TABLE fixed_lists ADD CONSTRAINT fixed_lists_status_check CHECK (status::text = ANY (ARRAY['Draft'::character varying, 'Submitted'::character varying, 'Approved'::character varying, 'Rejected'::character varying]::text[]))");
        }
    }
};
