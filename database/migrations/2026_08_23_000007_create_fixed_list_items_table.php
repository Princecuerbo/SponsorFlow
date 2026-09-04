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
        Schema::create('fixed_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_list_id')
                ->constrained('fixed_lists')
                ->cascadeOnDelete();
            $table->string('student_name', 150);
            $table->string('student_id_number', 50);
            $table->string('course', 150);
            $table->unsignedTinyInteger('year_level');
            $table->boolean('is_sle_fhe_verified')->default(false);
            $table->enum('status', ['Pending', 'Verified', 'Eligible', 'Ineligible'])
                ->default('Pending');
            $table->timestamps();

            $table->unique(
                ['fixed_list_id', 'student_id_number'],
                'fixed_list_items_list_student_unique'
            );
            $table->index('student_id_number');
            $table->index('status');
            $table->index('is_sle_fhe_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_list_items');
    }
};
