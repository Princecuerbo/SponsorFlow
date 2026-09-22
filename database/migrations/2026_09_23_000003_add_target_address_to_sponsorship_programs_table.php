<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sponsorship_programs', 'target_province')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->string('target_province', 255)->nullable()->after('address_requirement');
            });
        }

        if (! Schema::hasColumn('sponsorship_programs', 'target_municipality')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->string('target_municipality', 255)->nullable()->after('target_province');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sponsorship_programs', 'target_municipality')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->dropColumn('target_municipality');
            });
        }

        if (Schema::hasColumn('sponsorship_programs', 'target_province')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->dropColumn('target_province');
            });
        }
    }
};
