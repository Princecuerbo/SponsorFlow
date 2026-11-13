<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master SLE-FHE registry: the anonymized official masterlist used as an
     * advisory cross-check during FASSG verification. Records are keyed by
     * student_id only and intentionally hold no names, birthdays, or contact
     * details, keeping the ingest within RA 10173 data-minimisation limits.
     */
    public function up(): void
    {
        Schema::create('sle_fhe_registries', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50);
            $table->string('sex', 20)->nullable();
            $table->string('civil_status', 40)->nullable();
            $table->string('purok', 150)->nullable();
            $table->string('barangay', 150)->nullable();
            $table->string('municipality', 150)->nullable();
            $table->string('course', 191)->nullable();
            $table->string('year_level', 50)->nullable();
            $table->string('campus', 191)->nullable();
            $table->unsignedTinyInteger('district')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->timestamps();

            $table->unique('student_id');
            $table->index('municipality');
            $table->index('campus');
            $table->index('district');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sle_fhe_registries');
    }
};
