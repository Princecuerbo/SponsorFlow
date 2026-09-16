<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->boolean('is_manually_endorsed')->default(false)->after('requested_documents');
            $table->foreignId('endorsed_by_id')->nullable()->constrained('users')->nullOnDelete()->after('is_manually_endorsed');
            $table->timestamp('endorsed_at')->nullable()->after('endorsed_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropForeign(['endorsed_by_id']);
            $table->dropColumn(['is_manually_endorsed', 'endorsed_by_id', 'endorsed_at']);
        });
    }
};
