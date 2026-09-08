<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table): void {
            $table->boolean('requires_relative_verification')->default(false)->after('address_requirement');
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_programs', function (Blueprint $table): void {
            $table->dropColumn('requires_relative_verification');
        });
    }
};