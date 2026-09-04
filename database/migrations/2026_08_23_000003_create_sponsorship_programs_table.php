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
        Schema::create('sponsorship_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')
                ->constrained('sponsors')
                ->cascadeOnDelete();
            $table->string('program_name', 200);
            $table->enum('category', ['Group', 'Individual', 'Employee-Based']);
            $table->unsignedSmallInteger('available_slots');
            $table->enum('status', ['Open', 'Closed', 'Expired'])->default('Open');
            $table->decimal('min_gpa', 3, 2)->nullable();
            $table->string('target_course', 150)->nullable();
            $table->string('address_requirement', 255)->nullable();
            $table->timestamps();

            $table->index('program_name');
            $table->index('category');
            $table->index('status');
            $table->index('target_course');
            $table->index(['sponsor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorship_programs');
    }
};
