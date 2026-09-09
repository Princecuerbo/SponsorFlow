<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sponsorship_programs', 'application_deadline')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->date('application_deadline')->nullable()->after('end_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sponsorship_programs', 'application_deadline')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->dropColumn('application_deadline');
            });
        }
    }
};