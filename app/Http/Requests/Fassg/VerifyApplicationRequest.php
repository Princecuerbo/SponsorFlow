<?php

namespace App\Http\Requests\Fassg;

use App\Enums\DocumentType;
use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class VerifyApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFassg() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('grades_verified') && ! $this->has('confirm_gwa')) {
            $merge['confirm_gwa'] = $this->input('grades_verified');
        }
        if ($this->has('address_verified') && ! $this->has('confirm_address')) {
            $merge['confirm_address'] = $this->input('address_verified');
        }
        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $application = $this->route('application');
        if (is_scalar($application)) {
            $application = Application::query()->find($application);
        }
        if (! $application instanceof Application) {
            return ['confirm_eligibility' => ['required', 'accepted']];
        }
        $requirements = $application->sponsorshipProgram->beneficiaryQualificationRequirements();
        $documents = $application->sponsorshipProgram->beneficiaryRequiredDocumentTypes();
        $rules = ['confirm_eligibility' => ['required', 'accepted']];
        foreach (['gpa_submitted' => 'confirm_gwa', 'address_submitted' => 'confirm_address'] as $field => $confirmation) {
            $rules[$confirmation] = $requirements[$field] ? ['required', 'accepted'] : ['nullable', 'accepted'];
        }
        if (in_array(DocumentType::CertificateOfRegistration->value, $documents, true)) {
            $rules['confirm_cor'] = ['required', 'accepted'];
        }
        if (in_array(DocumentType::EmployeeProofOfKinship->value, $documents, true)) {
            $rules['confirm_kinship'] = ['required', 'accepted'];
        }
        if ($application->is_auto_provisioned) {
            $rules['gpa_submitted'] = [$requirements['gpa_submitted'] ? 'required' : 'nullable', 'numeric', 'between:1,5'];
            $rules['address_submitted'] = [$requirements['address_submitted'] ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['is_rural_submitted'] = [$requirements['is_rural_submitted'] ? 'required' : 'nullable', 'boolean'];
            foreach (['employee_name', 'employee_id_number', 'employee_relationship'] as $field) {
                $rules[$field] = [$application->sponsorshipProgram->requiresBeneficiaryRelationshipEvidence() ? 'required' : 'nullable', 'string', 'max:255'];
            }
            $types = array_map(fn ($type) => $type->value, DocumentType::cases());
            $rules['qualification_documents'] = ['sometimes', 'array:'.implode(',', $types)];
            $rules['qualification_documents.*'] = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_gwa.accepted' => 'Confirm that the grade slip matches the submitted GWA.',
            'confirm_address.accepted' => 'Confirm that the proof of residence and barangay certificate match the submitted address.',
            'confirm_cor.accepted' => 'Confirm that the Certificate of Registration (COR) matches current enrollment.',
            'confirm_kinship.accepted' => 'Confirm that the Employee ID / Proof of Kinship matches the submitted employee dependency details.',
        ];
    }
}
