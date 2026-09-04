<?php

namespace App\Http\Requests\Fassg;

use Illuminate\Foundation\Http\FormRequest;

class ImportFixedListRequest extends FormRequest
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
            'file' => ['required_without:list_file', 'nullable', 'file', 'mimes:csv,txt', 'max:2048'],
            'list_file' => ['required_without:file', 'nullable', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
