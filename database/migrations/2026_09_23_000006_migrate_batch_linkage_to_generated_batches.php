<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Generated batches earn their own approval chain; the legacy
        // fixed_list_id column is kept (nullable) for historical approvals.
        Schema::table('sponsor_approvals', function (Blueprint $table): void {
            $table->unsignedBigInteger('generated_batch_id')->nullable();
            $table->index('generated_batch_id');

            // Legacy fixed_list_id becomes nullable: generated-batch approvals
            // legitimately have no linked fixed list.
            $table->unsignedBigInteger('fixed_list_id')->nullable()->change();
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('sponsor_approvals', function (Blueprint $table): void {
                $table->foreign('generated_batch_id')->references('id')->on('generated_batches')->nullOnDelete();
            });
        }

        // Generated batches are pure queue artifacts in their own tables.
        // fixed_list_items keeps only the manually encoded / imported SLE-FHE
        // fixed list data.
        Schema::table('fixed_list_items', function (Blueprint $table): void {
            $table->dropForeign(['application_id']);
            $table->dropColumn(['application_id', 'rank_position', 'origin_type']);
        });

        // Re-target applications.batch_id to generated_batches. SQLite cannot
        // add a foreign key to an existing table, but it does not enforce
        // foreign keys in tests either, so the lookup is guarded by driver.
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropForeign(['batch_id']);
        });

        // Legacy rows may still point at fixed_lists IDs that do not exist in
        // generated_batches. Under the fresh-start decision these links are
        // severed before the foreign key is re-targeted.
        DB::table('applications')->whereNotNull('batch_id')->update(['batch_id' => null]);

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('applications', function (Blueprint $table): void {
                $table->foreign('batch_id')->references('id')->on('generated_batches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Best-effort reversal; generated batch data is never backfilled.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('applications', function (Blueprint $table): void {
                $table->dropForeign(['batch_id']);
                $table->foreign('batch_id')->references('id')->on('fixed_lists')->nullOnDelete();
            });

            Schema::table('sponsor_approvals', function (Blueprint $table): void {
                $table->dropForeign(['generated_batch_id']);
            });
        }

        Schema::table('fixed_list_items', function (Blueprint $table): void {
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->enum('origin_type', ['fixed_list', 'ranked_queue'])->default('ranked_queue');
            $table->unsignedInteger('rank_position')->nullable();
        });

        Schema::table('sponsor_approvals', function (Blueprint $table): void {
            $table->dropIndex(['generated_batch_id']);
            $table->dropColumn('generated_batch_id');
        });
    }
};
