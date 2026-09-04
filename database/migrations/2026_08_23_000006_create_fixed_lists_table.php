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
        Schema::create('fixed_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsorship_program_id')
                ->constrained('sponsorship_programs')
                ->cascadeOnDelete();
            $table->string('batch_name', 150);
            $table->foreignId('uploaded_by_fassg_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->unsignedInteger('total_names')->default(0);
            $table->enum('status', ['Draft', 'Submitted', 'Approved', 'Rejected'])
                ->default('Draft');
            $table->timestamps();

            $table->index('batch_name');
            $table->index('status');
            $table->index(['sponsorship_program_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_lists');
    }
};
