<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsorship_program_id')
                ->constrained('sponsorship_programs')
                ->cascadeOnDelete();
            $table->foreignId('fixed_list_id')
                ->nullable()
                ->constrained('fixed_lists')
                ->nullOnDelete();
            $table->string('batch_name', 150);
            $table->unsignedInteger('total_slots')->default(0);
            $table->enum('status', ['Saved', 'Submitted', 'Approved', 'Rejected'])
                ->default('Saved');
            $table->foreignId('created_by_fassg_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('fassg_assigned_at')->nullable();
            $table->foreignId('fassg_assigned_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('batch_name');
            $table->index('status');
            $table->index(['sponsorship_program_id', 'status']);
        });

        Schema::create('generated_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_batch_id')
                ->constrained('generated_batches')
                ->cascadeOnDelete();
            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();
            $table->unsignedInteger('rank_position')->nullable();
            $table->enum('origin_type', ['fixed_list', 'ranked_queue'])
                ->default('ranked_queue');
            $table->timestamps();

            $table->index('generated_batch_id');
            $table->index('application_id');
            $table->index(['generated_batch_id', 'rank_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_batch_items');
        Schema::dropIfExists('generated_batches');
    }
};
