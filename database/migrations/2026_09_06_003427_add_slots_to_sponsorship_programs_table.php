<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            if (!Schema::hasColumn('sponsorship_programs', 'slots')) {
                $table->integer('slots')->default(0);
            }
            if (!Schema::hasColumn('sponsorship_programs', 'available_slots')) {
                $table->integer('available_slots')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->dropColumn(['slots', 'available_slots']);
        });
    }
};
