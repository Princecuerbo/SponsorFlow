<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Definitions checked against the supplied MariaDB schema exports.
        // No historical actors, decisions, or submission snapshots are inferred.
        Schema::table('generated_batches', function (Blueprint $table): void {
            $table->unsignedInteger('submission_version')->default(0);
            $table->json('submitted_roster')->nullable();
        });

        Schema::table('generated_batch_items', function (Blueprint $table): void {
            $table->foreignId('fixed_list_item_id')->nullable()
                ->constrained('fixed_list_items')->restrictOnDelete();
        });

        Schema::table('sponsor_approvals', function (Blueprint $table): void {
            $table->unsignedInteger('submission_version')->nullable();
            $table->string('approval_document_path', 500)->nullable()->change();
            $table->unsignedBigInteger('uploaded_by_sponsor_id')->nullable()->change();
            foreach (['decided', 'fassg_recorded', 'accounting_forwarded'] as $action) {
                $table->foreignId($action.'_by_id')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamp($action.'_at')->nullable();
            }
            $table->index(['generated_batch_id', 'submission_version'], 'sponsor_approvals_submission_index');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Review and preserve batch decision evidence before manually reversing this migration.');
    }
};
