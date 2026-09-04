<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['setting_key' => 'max_login_attempts', 'setting_value' => '5', 'description' => 'Maximum failed login attempts before throttling.'],
            ['setting_key' => 'session_timeout_minutes', 'setting_value' => '120', 'description' => 'Session lifetime in minutes.'],
            ['setting_key' => 'maintenance_mode', 'setting_value' => 'false', 'description' => 'Whether the application is in maintenance mode.'],
        ] as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['setting_key' => $setting['setting_key']],
                [...$setting, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('setting_key', ['max_login_attempts', 'session_timeout_minutes', 'maintenance_mode'])
            ->delete();
    }
};
