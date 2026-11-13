<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public const LIMITS = [
        'max_login_attempts' => [1, 20],
        'session_timeout_minutes' => [1, 1440],
    ];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()?->isActive();
    }

    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array:maintenance_mode,max_login_attempts,session_timeout_minutes', 'min:1'],
            'settings.maintenance_mode' => ['sometimes', 'required', Rule::in(['true', 'false', '1', '0'])],
        ];
        foreach (self::LIMITS as $key => [$min, $max]) {
            $rules['settings.'.$key] = ['sometimes', 'required', 'integer', "between:$min,$max"];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'settings.max_login_attempts' => 'maximum login attempts',
            'settings.session_timeout_minutes' => 'session timeout in minutes',
            'settings.maintenance_mode' => 'maintenance mode',
        ];
    }
}
