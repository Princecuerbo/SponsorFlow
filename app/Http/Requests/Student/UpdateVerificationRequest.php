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
        $profile = $this->user()?->studentProfile;
        $profileId = $profile?->id;

        return [
            'student_id_number' => [
                'required',
                'string',
                'max:50',
                'regex:/^\d{4}-\d{4,6}$/',
                Rule::unique('student_profiles', 'student_id_number')->ignore($profileId),
            ],
            'academic_program_id' => [
                'bail',
                Rule::requiredIf($this->has('academic_program_id')
                    || (blank($profile?->academic_program_id) && blank($profile?->course))),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_int($value) && ! is_string($value)) {
                        $fail('The academic program id must be an integer.');
                    }
                },
                'integer', 'min:1', 'regex:/\A[1-9][0-9]*\z/',
                Rule::exists('academic_programs', 'program_id')
                    ->where('is_active', true)->where('is_undergraduate', true),
            ],
            // Legacy course text can be retained, but cannot select a new program.
            'course' => [
                'nullable', 'string', 'max:150',
                Rule::when(! $this->has('academic_program_id'), [Rule::in([$profile?->course ?? ''])]),
            ],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
            'gender' => ['nullable', 'string', 'in:Male,Female'],
            'birthdate' => ['nullable', 'date', 'before:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id_number.regex' => 'The student ID must look like 2024-00001.',
            'course.in' => 'Select an active undergraduate Academic Program to change your course.',
        ];
    }
}
