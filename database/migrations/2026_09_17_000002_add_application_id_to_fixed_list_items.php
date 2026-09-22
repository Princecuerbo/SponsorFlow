<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'Endorsed' to fixed_list_items.status enum
        // MySQL: MODIFY COLUMN with expanded ENUM list
        // PostgreSQL: Laravel enum columns use CHECK constraints, not custom types.
        //   We drop the old constraint and add a new one with the expanded values.
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE fixed_list_items MODIFY COLUMN status ENUM('Pending','Verified','Eligible','Ineligible','Endorsed') NOT NULL DEFAULT 'Pending'");
        } elseif ($driver === 'pgsql') {
            // Drop the existing CHECK constraint (Laravel names it "{table}_{column}_check")
            DB::statement('ALTER TABLE fixed_list_items DROP CONSTRAINT IF EXISTS fixed_list_items_status_check');
            // Re-create with expanded values
            DB::statement("ALTER TABLE fixed_list_items ADD CONSTRAINT fixed_list_items_status_check CHECK (status::text = ANY (ARRAY['Pending'::character varying, 'Verified'::character varying, 'Eligible'::character varying, 'Ineligible'::character varying, 'Endorsed'::character varying]::text[]))");
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

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE fixed_list_items MODIFY COLUMN status ENUM('Pending','Verified','Eligible','Ineligible') NOT NULL DEFAULT 'Pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE fixed_list_items DROP CONSTRAINT IF EXISTS fixed_list_items_status_check');
            DB::statement("ALTER TABLE fixed_list_items ADD CONSTRAINT fixed_list_items_status_check CHECK (status::text = ANY (ARRAY['Pending'::character varying, 'Verified'::character varying, 'Eligible'::character varying, 'Ineligible'::character varying]::text[]))");
        }
    }
};
