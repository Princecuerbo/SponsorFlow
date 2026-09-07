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
            'academic_program_id' => ['required_without:course', 'exists:academic_programs,program_id'],
            'course' => ['nullable', 'string', 'max:150'],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'gender' => ['nullable', 'string', 'in:Male,Female'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:500'],
            'municipality' => ['nullable', 'string', 'in:Mati City,Baganga,Banaybanay,Boston,Caraga,Cateel,Governor Generoso,Lupon,Manay,San Isidro,Tarragona'],
            'barangay' => ['nullable', 'string', 'max:150'],
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
        $merges = [
            'is_rural' => $this->boolean('is_rural'),
        ];

        if ($this->filled('municipality') && ! $this->filled('barangay')) {
            $merges['barangay'] = $this->input('municipality');
        } elseif ($this->filled('barangay') && ! $this->filled('municipality')) {
            $merges['municipality'] = $this->input('barangay');
        }

        $this->merge($merges);
    }
}
