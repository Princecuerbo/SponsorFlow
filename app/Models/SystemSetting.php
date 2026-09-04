<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = ['setting_key', 'setting_value', 'description'];

    public static function get(string $settingKey, mixed $default = null): mixed
    {
        if (! app('db')->getSchemaBuilder()->hasTable('system_settings')) {
            return $default;
        }

        $setting = static::query()->where('setting_key', $settingKey)->first();

        if ($setting === null) {
            return $default;
        }

        return $setting->setting_value ?? $default;
    }
}