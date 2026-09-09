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
            'available_slots' => ['required', 'integer', 'min:0', 'lte:total_slots', 'max:1000'],
            'total_slots' => ['required', 'integer', 'min:1', 'max:1000'],
            'end_date' => ['nullable', 'date'],
            'application_deadline' => [
                'nullable',
                'date',
                Rule::when($this->filled('end_date'), ['before_or_equal:end_date']),
            ],
            'min_gpa' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'target_course' => ['nullable', 'string', 'max:150'],
            'address_requirement' => ['nullable', 'string', 'max:255'],
            'requires_relative_verification' => ['nullable', 'boolean'],
            'academic_program_ids' => ['nullable', 'array'],
            'academic_program_ids.*' => ['exists:academic_programs,program_id'],
            'eligible_year_levels' => ['nullable', 'array'],
            'eligible_year_levels.*' => ['in:1,2,3,4'],
            'eligible_campuses' => ['nullable', 'array'],
            'eligible_campuses.*' => ['distinct', 'string', 'max:150'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['distinct', 'string', 'max:150'],
        ];
    }
}
