<?php

namespace App\Http\Requests\Fassg;

use App\Enums\DocumentType;
use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class ApproveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFassg() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'confirm_gwa' => ['required', 'accepted'],
            'confirm_address' => ['required', 'accepted'],
        ];

        $application = $this->route('application');

        if (is_scalar($application)) {
            $application = $application !== null ? Application::query()->find($application) : null;
        }

        if ($application instanceof Application) {
            $required = $application->sponsorshipProgram?->requiredDocumentCanonicalValues() ?? [];
            $uploaded = $application->documents
                ->pluck('document_type')
                ->map(fn ($type): string => DocumentType::canonicalValue($type))
                ->unique()
                ->values()
                ->all();

            $combined = array_values(array_unique(array_merge($required, $uploaded)));

            if (in_array(DocumentType::CertificateOfRegistration->value, $combined, true)) {
                $rules['confirm_cor'] = ['required', 'accepted'];
            }

            if (in_array(DocumentType::EmployeeProofOfKinship->value, $combined, true)) {
                $rules['confirm_kinship'] = ['required', 'accepted'];
            }
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
