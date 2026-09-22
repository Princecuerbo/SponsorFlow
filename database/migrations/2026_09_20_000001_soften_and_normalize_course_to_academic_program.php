<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize the legacy free-text `course` column toward the
     * `academic_programs` reference table:
     *
     * 1. Backfill `student_profiles.academic_program_id` from matching
     *    `academic_programs` rows by name/code.
     * 2. Drop the `course` index and make the column nullable so it becomes a
     *    legacy/display fallback rather than a hard requirement.
     * 3. Ensure `academic_program_id` is indexed for the query filters that
     *    now resolve academic programs via the FK.
     */
    public function up(): void
    {
        DB::table('student_profiles')
            ->select('id', 'course')
            ->whereNull('academic_program_id')
            ->whereNotNull('course')
            ->orderBy('id')
            ->chunkById(500, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $needle = strtolower(trim((string) $profile->course));

                    if ($needle === '') {
                        continue;
                    }

                    $program = DB::table('academic_programs')
                        ->where('is_active', true)
                        ->where(function ($query) use ($needle): void {
                            $query->whereRaw('LOWER(name) = ?', [$needle])
                                ->orWhereRaw('LOWER(code) = ?', [$needle]);
                        })
                        ->value('program_id');

                    if ($program !== null) {
                        DB::table('student_profiles')
                            ->where('id', $profile->id)
                            ->update(['academic_program_id' => $program]);
                    }
                }
            });

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropIndex('student_profiles_course_index');
            $table->string('course', 150)->nullable()->change();
        });

        $coversAcademicProgram = collect(Schema::getIndexes('student_profiles'))
            ->contains(fn (array $index): bool => in_array('academic_program_id', $index['columns'], true));

        if (! $coversAcademicProgram) {
            Schema::table('student_profiles', function (Blueprint $table): void {
                $table->index('academic_program_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->string('course', 150)->nullable(false)->change();
            $table->index('course');
        });
    }
};
