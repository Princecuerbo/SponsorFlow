<?php

namespace App\Http\Requests\Sponsor;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSponsor() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'approval_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
