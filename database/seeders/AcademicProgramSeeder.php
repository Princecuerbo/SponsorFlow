<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
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
        ];

        foreach ($programs as $program) {
            DB::table('academic_programs')->updateOrInsert(
                ['code' => $program['code']],
                array_merge($program, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
