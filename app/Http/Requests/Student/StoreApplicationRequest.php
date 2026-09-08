<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Models\SponsorshipProgram;
use Illuminate\Validation\Rule;

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
        $optionalFileRules = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        $program = SponsorshipProgram::query()
            ->find($this->integer('sponsorship_program_id'));

        $requiredDocuments = $program !== null
            ? (array) ($program->required_documents ?? [])
            : [];
        $hasDocument = fn (string $label): bool => in_array($label, $requiredDocuments, true);

        $requiresInstitutionalVerification = $program !== null
            && ($program->category?->value === 'Employee-Based'
                || (bool) $program->requires_relative_verification);

        $relationshipOptions = ['Parent', 'Spouse', 'Sibling', 'Guardian'];

        return [
            'sponsorship_program_id' => ['required', 'integer', 'exists:sponsorship_programs,id'],
            'current_gpa' => ['required_without:gpa_submitted', 'numeric', 'min:1.00', 'max:5.00'],
            'gpa_submitted' => ['required_without:current_gpa', 'numeric', 'min:1.00', 'max:5.00'],
            'current_address' => ['required_without:address_submitted', 'string', 'max:255'],
            'address_submitted' => ['required_without:current_address', 'string', 'max:255'],
            'is_rural_submitted' => ['required', 'boolean'],
            'employee_name' => $requiresInstitutionalVerification
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'employee_id_number' => $requiresInstitutionalVerification
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'employee_relationship' => $requiresInstitutionalVerification
                ? ['required', Rule::in($relationshipOptions)]
                : ['nullable', Rule::in($relationshipOptions)],
            'grade_slip' => $optionalFileRules,
            'certificate_of_grades' => $optionalFileRules,
            'proof_of_residence' => $hasDocument('Proof of Residence / Barangay Cert') ? $fileRules : $optionalFileRules,
            'barangay_certification' => $optionalFileRules,
            'barangay_cert' => $optionalFileRules,
            'indigency_doc' => $hasDocument('Certificate of Indigency') ? $fileRules : $optionalFileRules,
            'cor_doc' => $hasDocument('Certificate of Registration (COR)') ? $fileRules : $optionalFileRules,
            'employee_id_doc' => $hasDocument('Employee ID / Proof of Kinship') ? $fileRules : $optionalFileRules,
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
            'grade_slip' => 'Grade Slip / TOR',
            'certificate_of_grades' => 'Grade Slip / TOR',
            'proof_of_residence' => 'Proof of Residence / Barangay Certificate',
            'barangay_certification' => 'Barangay Certificate',
            'barangay_cert' => 'Barangay Certificate',
            'indigency_doc' => 'Certificate of Indigency',
            'cor_doc' => 'Certificate of Registration (COR)',
            'employee_id_doc' => 'Employee ID / Proof of Kinship',
            'employee_name' => 'Relative Employee Name',
            'employee_id_number' => 'Employee ID Number',
            'employee_relationship' => 'Relationship to Employee',
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
            $program = SponsorshipProgram::query()
                ->find($this->integer('sponsorship_program_id'));

            if ($program === null) {
                // Program already validated by the rules() method; skip extra checks.
                return;
            }

            $requiredDocuments = (array) ($program->required_documents ?? []);

            if (in_array('Report Card / Certificate of Grades', $requiredDocuments, true)
                && ! $this->hasFile('grade_slip')
                && ! $this->hasFile('certificate_of_grades')) {
                $validator->errors()->add('grade_slip', 'The Grade Slip / TOR field is required.');
            }

            if (in_array('Proof of Residence / Barangay Cert', $requiredDocuments, true)
                && ! $this->hasFile('proof_of_residence')
                && ! $this->hasFile('barangay_certification')
                && ! $this->hasFile('barangay_cert')) {
                $validator->errors()->add('proof_of_residence', 'The Proof of Residence / Barangay Certificate field is required.');
            }

            $profile = $this->user()?->studentProfile;

            if ($profile === null) {
                return;
            }

            // Known urban municipalities in Davao Oriental where rural grants don't apply.
            // Keep in sync with the front-end list in create.blade.php.
            $urbanMunicipalities = ['Mati City', 'Mati', 'Matiao'];
            $profileMunicipality = trim((string) ($profile->municipality ?? ''));
            $profileIsUrban = ! $profile->is_rural
                || in_array($profileMunicipality, $urbanMunicipalities, true);

            $programAddressReq = strtolower((string) ($program->address_requirement ?? ''));
            $programRequiresRural = filled($program->address_requirement)
                && str_contains($programAddressReq, 'rural');
            $programRequiresUrban = filled($program->address_requirement)
                && str_contains($programAddressReq, 'urban');

            // 1. Program requires Rural, but student profile is Urban (or is_rural is false)
            if ($programRequiresRural && (! $profile->is_rural || $profileIsUrban)) {
                $validator->errors()->add(
                    'application',
                    'This program is intended for Rural residents, but your profile address is classified as Urban. '
                    . 'Applying for Rural-specific grants requires a valid rural address or Barangay certification.',
                );
                $validator->errors()->add(
                    'is_rural_submitted',
                    'Your profile is classified as Urban. You cannot declare rural residency for a Rural-specific program.',
                );
            }

            // 2. Program requires Urban, but student profile is Rural (is_rural is true)
            if ($programRequiresUrban && ($profile->is_rural || ! $profileIsUrban)) {
                $validator->errors()->add(
                    'application',
                    'This program is intended for Urban residents, but your profile address is classified as Rural. '
                    . 'Applying for Urban-specific grants requires a valid urban address or certification.',
                );
                $validator->errors()->add(
                    'is_rural_submitted',
                    'Your profile is classified as Rural. You cannot apply for an Urban-specific program.',
                );
            }
        });
    }
}
