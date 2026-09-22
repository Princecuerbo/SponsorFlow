<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE fixed_lists MODIFY COLUMN status ENUM('Draft','Submitted','Approved','Rejected','Saved','Finalized') NOT NULL DEFAULT 'Draft'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE fixed_lists DROP CONSTRAINT IF EXISTS fixed_lists_status_check');
            DB::statement("ALTER TABLE fixed_lists ADD CONSTRAINT fixed_lists_status_check CHECK (status::text = ANY (ARRAY['Draft'::character varying, 'Submitted'::character varying, 'Approved'::character varying, 'Rejected'::character varying, 'Saved'::character varying, 'Finalized'::character varying]::text[]))");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE fixed_lists MODIFY COLUMN status ENUM('Draft','Submitted','Approved','Rejected','Saved') NOT NULL DEFAULT 'Draft'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE fixed_lists DROP CONSTRAINT IF EXISTS fixed_lists_status_check');
            DB::statement("ALTER TABLE fixed_lists ADD CONSTRAINT fixed_lists_status_check CHECK (status::text = ANY (ARRAY['Draft'::character varying, 'Submitted'::character varying, 'Approved'::character varying, 'Rejected'::character varying, 'Saved'::character varying]::text[]))");
        }
    }
};
