<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sponsorship_programs', 'end_date')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->date('end_date')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sponsorship_programs', 'end_date')) {
            Schema::table('sponsorship_programs', function (Blueprint $table): void {
                $table->dropColumn('end_date');
            });
        }
    }
};