<?php

namespace App\Http\Requests\Fassg;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedListItemRequest extends FormRequest
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
            'student_name' => ['required', 'string', 'max:150'],
            'student_id_number' => ['required', 'string', 'max:50', 'regex:/^\d{4}-\d{4,6}$/'],
            'course' => ['required', 'string', 'max:150'],
            'year_level' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
