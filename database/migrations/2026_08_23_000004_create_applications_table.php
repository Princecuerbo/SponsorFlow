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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->cascadeOnDelete();
            $table->foreignId('sponsorship_program_id')
                ->constrained('sponsorship_programs')
                ->cascadeOnDelete();
            $table->decimal('gpa_submitted', 3, 2);
            $table->string('address_submitted', 255);
            $table->boolean('is_rural_submitted')->default(false);
            $table->enum('status', [
                'Pending',
                'Verified',
                'Approved',
                'Rejected',
                'Ongoing',
                'Expired',
            ])->default('Pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_profile_id', 'sponsorship_program_id'],
                'applications_student_program_unique'
            );
            $table->index('status');
            $table->index('submitted_at');
            $table->index(['sponsorship_program_id', 'status']);
            $table->index(['student_profile_id', 'status']);
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->foreign('active_sponsorship_id')
                ->references('id')
                ->on('applications')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropForeign(['active_sponsorship_id']);
        });

        Schema::dropIfExists('applications');
    }
};
