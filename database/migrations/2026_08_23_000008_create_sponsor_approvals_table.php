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
        Schema::create('sponsor_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsorship_program_id')
                ->constrained('sponsorship_programs')
                ->cascadeOnDelete();
            $table->foreignId('fixed_list_id')
                ->constrained('fixed_lists')
                ->cascadeOnDelete();
            $table->string('approval_document_path', 500);
            $table->enum('confirmation_status', ['Pending', 'Confirmed', 'Rejected'])
                ->default('Pending');
            $table->foreignId('uploaded_by_sponsor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['sponsorship_program_id', 'fixed_list_id'],
                'sponsor_approvals_program_list_unique'
            );
            $table->index('confirmation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsor_approvals');
    }
};
