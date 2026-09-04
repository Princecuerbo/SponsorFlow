<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreApplicationRequest extends FormRequest
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
        $fileRules = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        return [
            'sponsorship_program_id' => ['required', 'integer', 'exists:sponsorship_programs,id'],
            'current_gpa' => ['required_without:gpa_submitted', 'numeric', 'min:1.00', 'max:5.00'],
            'gpa_submitted' => ['required_without:current_gpa', 'numeric', 'min:1.00', 'max:5.00'],
            'current_address' => ['required_without:address_submitted', 'string', 'max:255'],
            'address_submitted' => ['required_without:current_address', 'string', 'max:255'],
            'is_rural_submitted' => ['required', 'boolean'],
            'grade_slip' => array_merge(['nullable'], array_slice($fileRules, 1)),
            'certificate_of_grades' => array_merge(['nullable'], array_slice($fileRules, 1)),
            'proof_of_residence' => $fileRules,
            'barangay_certification' => array_merge(['nullable'], array_slice($fileRules, 1)),
            'barangay_cert' => array_merge(['nullable'], array_slice($fileRules, 1)),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_gpa' => 'Current GPA',
            'current_address' => 'Current Address',
            'grade_slip' => 'Grade Slip',
            'certificate_of_grades' => 'Grade Slip',
            'proof_of_residence' => 'Proof of Residence',
            'barangay_certification' => 'Barangay Certificate',
            'barangay_cert' => 'Barangay Certificate',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'current_gpa' => $this->input('current_gpa', $this->input('gpa_submitted')),
            'current_address' => $this->input('current_address', $this->input('address_submitted')),
            'is_rural_submitted' => $this->boolean('is_rural_submitted'),
        ]);

        if (! $this->hasFile('grade_slip') && $this->hasFile('certificate_of_grades')) {
            $this->files->set('grade_slip', $this->file('certificate_of_grades'));
        }

        if (! $this->hasFile('barangay_certification') && $this->hasFile('barangay_cert')) {
            $this->files->set('barangay_certification', $this->file('barangay_cert'));
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasFile('grade_slip') && ! $this->hasFile('certificate_of_grades')) {
                $validator->errors()->add('grade_slip', 'The Grade Slip field is required.');
            }

            if (! $this->hasFile('barangay_certification') && ! $this->hasFile('barangay_cert')) {
                $validator->errors()->add('barangay_certification', 'The Barangay Certificate field is required.');
            }
        });
    }
}
