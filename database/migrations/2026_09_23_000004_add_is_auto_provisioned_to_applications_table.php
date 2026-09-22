<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->boolean('is_auto_provisioned')->default(false)->after('is_manually_endorsed');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('is_auto_provisioned');
        });
    }
};
