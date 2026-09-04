<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_academic_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsorship_program_id')->constrained('sponsorship_programs')->cascadeOnDelete();
            $table->unsignedBigInteger('academic_program_id');
            $table->foreign('academic_program_id')->references('program_id')->on('academic_programs')->cascadeOnDelete();
            $table->timestamps();

            // Provide a custom short name to avoid the 64-character MySQL identifier limit
            $table->unique(['sponsorship_program_id', 'academic_program_id'], 'prog_acad_prog_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_academic_program');
    }
};
