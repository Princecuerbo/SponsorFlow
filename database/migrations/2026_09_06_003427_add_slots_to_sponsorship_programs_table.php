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
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->integer('slots')->default(0);
            $table->integer('available_slots')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->dropColumn(['slots', 'available_slots']);
        });
    }
};
