<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('last_login_at')->nullable()->after('status');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->text('details')->nullable()->after('target_module');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', fn (Blueprint $table) => $table->dropColumn('details'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('last_login_at'));
    }
};