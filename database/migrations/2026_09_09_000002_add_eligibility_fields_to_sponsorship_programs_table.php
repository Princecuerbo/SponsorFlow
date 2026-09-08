<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->json('eligible_campuses')->nullable()->after('eligible_year_levels');
            $table->json('required_documents')->nullable()->after('eligible_campuses');
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table) {
            $table->dropColumn(['eligible_campuses', 'required_documents']);
        });
    }
};