<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AcademicProgramSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $existing = DB::table('academic_programs')->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();
            // Validate the complete set before inserting. Never rewrite reviewed
            // codes, names, flags, IDs or their student/program associations.
            foreach (self::missingPrograms($existing) as $program) {
                DB::table('academic_programs')->insert([
                    ...$program, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public static function definitions(): array
    {
        return [
            // College of Education
            ['code' => 'BEEd', 'name' => 'Bachelor of Elementary Education', 'short_name' => 'Elem Ed', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSEd-Eng', 'name' => 'Bachelor of Secondary Education major in English', 'short_name' => 'Sec Ed-Eng', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSEd-Math', 'name' => 'Bachelor of Secondary Education major in Mathematics', 'short_name' => 'Sec Ed-Math', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSEd-Sci', 'name' => 'Bachelor of Secondary Education major in Science', 'short_name' => 'Sec Ed-Sci', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSEd-Soc', 'name' => 'Bachelor of Secondary Education major in Social Studies', 'short_name' => 'Sec Ed-Soc', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],

            // College of Business & Management
            ['code' => 'BSBA-MM', 'name' => 'Bachelor of Science in Business Administration major in Marketing Management', 'short_name' => 'BSBA-MM', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSBA-FM', 'name' => 'Bachelor of Science in Business Administration major in Financial Management', 'short_name' => 'BSBA-FM', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSBA-HRDM', 'name' => 'Bachelor of Science in Business Administration major in Human Resource Development Management', 'short_name' => 'BSBA-HRDM', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSBA-OM', 'name' => 'Bachelor of Science in Business Administration major in Operations Management', 'short_name' => 'BSBA-OM', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSA', 'name' => 'Bachelor of Science in Accountancy', 'short_name' => 'BSA', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],

            // College of Computing & Information Sciences
            ['code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science', 'short_name' => 'BSCS', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology', 'short_name' => 'BSIT', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],

            // College of Agriculture & Food Science
            ['code' => 'BSAgr', 'name' => 'Bachelor of Science in Agriculture', 'short_name' => 'BSAgr', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSFoodTech', 'name' => 'Bachelor of Science in Food Technology', 'short_name' => 'BSFoodTech', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],

            // College of Engineering & Technology
            ['code' => 'BSCE', 'name' => 'Bachelor of Science in Civil Engineering', 'short_name' => 'BSCE', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSEE', 'name' => 'Bachelor of Science in Electrical Engineering', 'short_name' => 'BSEE', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSITech', 'name' => 'Bachelor of Science in Industrial Technology', 'short_name' => 'BSITech', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],

            // College of Arts & Sciences
            ['code' => 'AB-Eng', 'name' => 'Bachelor of Arts in English', 'short_name' => 'AB Eng', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'AB-PolSci', 'name' => 'Bachelor of Arts in Political Science', 'short_name' => 'AB PolSci', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSSW', 'name' => 'Bachelor of Science in Social Work', 'short_name' => 'BSSW', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],

            // College of Nursing & Allied Health
            ['code' => 'BSN', 'name' => 'Bachelor of Science in Nursing', 'short_name' => 'BSN', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],

            // College of Criminal Justice
            ['code' => 'BSCrim', 'name' => 'Bachelor of Science in Criminology', 'short_name' => 'BSCrim', 'is_board_program' => true, 'is_undergraduate' => true, 'is_active' => true],

            // College of Public Administration
            ['code' => 'BPA', 'name' => 'Bachelor of Public Administration', 'short_name' => 'BPA', 'is_board_program' => false, 'is_undergraduate' => true, 'is_active' => true],

            // Graduate Programs
            ['code' => 'MED-EM', 'name' => 'Master of Education major in Educational Management', 'short_name' => 'MEd-EM', 'is_board_program' => false, 'is_undergraduate' => false, 'is_active' => true],
            ['code' => 'MPA', 'name' => 'Master in Public Administration', 'short_name' => 'MPA', 'is_board_program' => false, 'is_undergraduate' => false, 'is_active' => true],
            ['code' => 'MBA', 'name' => 'Master of Business Administration', 'short_name' => 'MBA', 'is_board_program' => false, 'is_undergraduate' => false, 'is_active' => true],

            // Previously reviewed additions; append without reordering legacy definitions.
            // No accreditation is asserted: unspecified board flags use the schema default.
            ['code' => 'BSAM', 'name' => 'Bachelor of Science in Agribusiness Management', 'short_name' => 'BSAM', 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSES', 'name' => 'Bachelor of Science in Environmental Science', 'short_name' => 'BSES', 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSHM', 'name' => 'Bachelor of Science in Hospitality Management', 'short_name' => 'BSHM', 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSDC', 'name' => 'Bachelor of Science in Development Communication', 'short_name' => 'BSDC', 'is_undergraduate' => true, 'is_active' => true],
            ['code' => 'BSM', 'name' => 'Bachelor of Science in Mathematics', 'short_name' => 'BSM', 'is_undergraduate' => true, 'is_active' => true],
            // Portal title confirmed by the user. This is NOT the legacy BSITech degree.
            ['code' => 'BITM', 'name' => 'Bachelor in Industrial Technology Management', 'short_name' => 'BITM', 'is_undergraduate' => true, 'is_active' => true],
        ];
    }

    /** Database-free preflight. Preserve an existing full-name match under its reviewed code. */
    public static function missingPrograms(array $existing): array
    {
        $normalize = static fn (string $value): string => mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
        $codes = [];
        $names = [];
        foreach ($existing as $program) {
            $code = $normalize($program['code']);
            $name = $normalize($program['name']);
            if (isset($codes[$code]) || isset($names[$name])) {
                throw new RuntimeException('Duplicate academic program code or name requires review before catalog seeding.');
            }
            $codes[$code] = $name;
            $names[$name] = true;
        }

        $missing = [];
        foreach (self::definitions() as $program) {
            $code = $normalize($program['code']);
            $name = $normalize($program['name']);
            if (isset($codes[$code]) && $codes[$code] !== $name) {
                throw new RuntimeException("Academic program code {$program['code']} already belongs to a different degree; no records were changed.");
            }
            if (isset($names[$name])) {
                continue;
            }
            $missing[] = $program;
            $codes[$code] = $name;
            $names[$name] = true;
        }

        return $missing;
    }
}
