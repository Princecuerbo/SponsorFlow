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
            // Stored as a JSON-encoded array, e.g. ["1","2","3","4"] or null for all year levels.
            $table->text('eligible_year_levels')->nullable()->after('address_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->dropColumn('eligible_year_levels');
        });
    }
};
