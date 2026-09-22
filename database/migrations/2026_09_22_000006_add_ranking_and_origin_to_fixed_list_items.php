<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Candidate source tags and persisted ranking on fixed list items:
        //  - is_fixed_list  : candidate was reserved from a sponsor-provided fixed list.
        //  - origin_type    : 'fixed_list' (Endorsed by Sponsor) vs 'ranked_queue'.
        //  - rank_position  : persisted 1..N order used when the batch is generated.
        Schema::table('fixed_list_items', function (Blueprint $table): void {
            $table->boolean('is_fixed_list')->default(false)->after('is_sle_fhe_verified');
            $table->enum('origin_type', ['fixed_list', 'ranked_queue'])->default('ranked_queue')->after('is_fixed_list');
            $table->unsignedInteger('rank_position')->nullable()->after('origin_type');
        });

        // Application-to-batch linkage so queued candidates can be flagged as
        // batched without re-scanning fixed_list_items every time.
        Schema::table('applications', function (Blueprint $table): void {
            $table->boolean('is_batched')->default(false)->after('is_manually_endorsed');
            $table->foreignId('batch_id')->nullable()->after('is_batched')->constrained('fixed_lists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['batch_id', 'is_batched']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE fixed_list_items DROP COLUMN is_fixed_list, DROP COLUMN origin_type, DROP COLUMN rank_position');
        } else {
            Schema::table('fixed_list_items', function (Blueprint $table): void {
                $table->dropColumn(['is_fixed_list', 'origin_type', 'rank_position']);
            });
        }
    }
};
