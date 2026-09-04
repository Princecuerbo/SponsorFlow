<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerificationRequest extends FormRequest
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
        $profileId = $this->user()?->studentProfile?->id;

        return [
            'student_id_number' => [
                'required',
                'string',
                'max:50',
                'regex:/^\d{4}-\d{4,6}$/',
                Rule::unique('student_profiles', 'student_id_number')->ignore($profileId),
            ],
            'course' => ['required', 'string', 'max:150'],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:500'],
            'barangay' => ['required', 'string', 'max:150'],
            'is_rural' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id_number.regex' => 'The student ID must look like 2024-00001.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_rural' => $this->boolean('is_rural'),
        ]);
    }
}
