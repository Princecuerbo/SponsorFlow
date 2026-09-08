<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->string('province', 150)->nullable()->after('municipality');
            $table->text('home_address')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropColumn(['province', 'home_address']);
        });
    }
};