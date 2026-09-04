<?php

namespace App\Http\Requests\Fassg;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedListRequest extends FormRequest
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
            'sponsorship_program_id' => ['required', 'integer', 'exists:sponsorship_programs,id'],
            'batch_name' => ['required', 'string', 'max:150'],
            'file' => ['nullable', 'file', 'mimes:csv,txt', 'max:2048'],
            'list_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
