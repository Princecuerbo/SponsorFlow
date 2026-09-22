<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sle_fhe_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->cascadeOnDelete();
            $table->string('province', 150)->nullable();
            $table->string('municipality_city', 150)->nullable();
            $table->string('barangay', 150)->nullable();
            $table->string('street_purok', 255)->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['student_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sle_fhe_requests');
    }
};
