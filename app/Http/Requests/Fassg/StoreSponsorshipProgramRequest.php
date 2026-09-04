<?php

namespace App\Http\Requests\Fassg;

use App\Enums\ProgramCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSponsorshipProgramRequest extends FormRequest
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
            'sponsor_id' => ['required', 'integer', 'exists:sponsors,id'],
            'program_name' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::enum(ProgramCategory::class)],
            'available_slots' => ['required', 'integer', 'min:1', 'max:1000'],
            'end_date' => ['nullable', 'date'],
            'min_gpa' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'target_course' => ['nullable', 'string', 'max:150'],
            'address_requirement' => ['nullable', 'string', 'max:255'],
        ];
    }
}
