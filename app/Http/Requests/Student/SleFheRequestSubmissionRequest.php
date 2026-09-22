<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SleFheRequestSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'province' => ['required', 'string', 'max:150'],
            'municipality_city' => ['required', 'string', 'max:150'],
            'barangay' => ['required', 'string', 'max:150'],
            'street_purok' => ['nullable', 'string', 'max:255'],
        ];
    }
}
