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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('student_id_number', 50);
            $table->string('course', 150);
            $table->unsignedTinyInteger('year_level');
            $table->date('birthdate')->nullable();
            $table->text('address')->nullable();
            $table->string('barangay', 150)->nullable();
            $table->boolean('is_rural')->default(false);
            $table->boolean('is_sle_fhe_verified')->default(false);
            $table->unsignedBigInteger('active_sponsorship_id')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->unique('student_id_number');
            $table->index('course');
            $table->index('year_level');
            $table->index('barangay');
            $table->index('is_rural');
            $table->index('is_sle_fhe_verified');
            $table->index('active_sponsorship_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
