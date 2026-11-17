<?php

namespace App\Http\Requests\Fassg;

use App\Models\AcademicProgram;
use App\Models\SleFheRegistry;
use App\Models\StudentProfile;
use App\Support\SleFhe\RegistryNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFixedListItemRequest extends FormRequest
{
    // The same supported levels used by student registration and FASSG program forms.
    public const YEAR_LEVELS = [1, 2, 3, 4, 5];

    private ?Collection $programs = null;

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
            'student_name' => ['required', 'string', 'max:150'],
            'student_id_number' => ['required', 'string', 'max:50', 'regex:/^\d{4}-\d{4,6}$/'],
            'course' => ['bail', 'required', 'string', 'max:150', Rule::in($this->availableAcademicPrograms()->pluck('name')->all())],
            'year_level' => ['bail', 'required', 'integer', Rule::in(self::YEAR_LEVELS)],
            'campus' => ['required', 'string', 'max:150'],
        ];

        if ($this->routeIs('fassg.fixed-lists.encode')) {
            $rules['fixed_list_id'] = ['required', 'integer', 'exists:fixed_lists,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'course.in' => 'Select an active undergraduate Academic Program from the official list.',
            'year_level.in' => 'Select a supported Year Level from 1 to 5.',
            'year_level.integer' => 'Select a supported Year Level from 1 to 5.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $program = $this->availableAcademicPrograms()->firstWhere('name', $this->input('course'));
            $normalize = fn ($value) => Str::lower(RegistryNormalizer::text($value) ?? '');
            $aliases = collect([$program->name, $program->code, $program->short_name])
                ->filter(fn ($value) => filled($value))->map($normalize)->all();
            $studentId = $this->string('student_id_number')->toString();

            // These are read-only consistency checks, never SLE-FHE verification.
            foreach ([
                'registered student profile' => $this->matchingStudentProfile($studentId),
                'institutional masterlist' => $this->matchingRegistryRecord($studentId),
            ] as $source => $record) {
                if ($record === null) {
                    continue;
                }

                $courseMismatch = $record instanceof StudentProfile && filled($record->academic_program_id)
                    ? (string) $record->academic_program_id !== (string) $program->program_id
                    : (filled($record->course) && ! in_array($normalize($record->course), $aliases, true));

                if ($courseMismatch) {
                    $validator->errors()->add('course', "Academic Program cannot be reconciled with the {$source}. Review the source record before encoding; it will not be overwritten.");
                }
            }
        }];
    }

    protected function availableAcademicPrograms(): Collection
    {
        return $this->programs ??= AcademicProgram::query()->where('is_active', true)
            ->where('is_undergraduate', true)->orderBy('name')->get();
    }

    protected function matchingStudentProfile(string $studentId): ?StudentProfile
    {
        return StudentProfile::query()->where('student_id_number', $studentId)->first();
    }

    protected function matchingRegistryRecord(string $studentId): ?SleFheRegistry
    {
        return SleFheRegistry::query()->byStudentId($studentId)->first();
    }
}
