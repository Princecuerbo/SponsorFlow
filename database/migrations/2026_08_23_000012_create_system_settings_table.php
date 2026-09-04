<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            ['setting_key' => 'max_login_attempts', 'setting_value' => '5', 'description' => 'Maximum failed login attempts before throttling.', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'session_timeout_minutes', 'setting_value' => '120', 'description' => 'Session lifetime in minutes.', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'maintenance_mode', 'setting_value' => 'false', 'description' => 'Whether the application is in maintenance mode.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};