<?php

namespace App\Http\Requests\Fassg;

use Illuminate\Foundation\Http\FormRequest;

class VerifyApplicationRequest extends FormRequest
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
        return [
            'grades_verified' => ['accepted'],
            'address_verified' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grades_verified.accepted' => 'Confirm that the grade slip matches the submitted GWA.',
            'address_verified.accepted' => 'Confirm that the proof of residence and barangay certificate match the submitted address.',
        ];
    }
}
