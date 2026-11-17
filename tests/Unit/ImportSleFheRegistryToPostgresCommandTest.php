<?php

namespace Tests\Unit;

use App\Console\Commands\ImportSleFheRegistryToPostgresCommand;
use PHPUnit\Framework\TestCase;

class ImportSleFheRegistryToPostgresCommandTest extends TestCase
{
    public function test_expected_record_count_is_1903(): void
    {
        $this->assertSame(1903, ImportSleFheRegistryToPostgresCommand::EXPECTED_RECORD_COUNT);
    }

    public function test_expected_target_database_is_render_postgres(): void
    {
        $this->assertSame('sponsorflow_db_jh27', ImportSleFheRegistryToPostgresCommand::DEFAULT_TARGET_DB);
    }

    public function test_expected_columns_match_migration_schema(): void
    {
        $expected = [
            'id',
            'student_id',
            'sex',
            'civil_status',
            'purok',
            'barangay',
            'municipality',
            'course',
            'year_level',
            'campus',
            'district',
            'is_verified',
            'created_at',
            'updated_at',
        ];

        $this->assertSame($expected, ImportSleFheRegistryToPostgresCommand::EXPECTED_COLUMNS);
    }

    public function test_flagged_courses_sum_to_seventy_one(): void
    {
        $flagged = ImportSleFheRegistryToPostgresCommand::EXPECTED_FLAGGED_COURSES;
        $this->assertCount(6, $flagged);
        $this->assertSame(71, array_sum($flagged));

        $this->assertSame(52, $flagged['BACHELOR OF INDUSTRIAL TECHNOLOGY MANAGEMENT (AUTOMOTIVE)']);
        $this->assertSame(11, $flagged['Bachelor of Industrial Technology Management (AUTOMOTIVE)']);
        $this->assertSame(4, $flagged['BACHELOR OF SECONDARY EDUCATION TLE-HE']);
        $this->assertSame(2, $flagged['BACHELOR OF SCIENCE IN POLITICAL SCIENCE']);
        $this->assertSame(1, $flagged['BACHELOR OF SCIENCE IN TECHNOLOGY PROGRAM']);
        $this->assertSame(1, $flagged['BACHELOR OF SIENCE IN MATH CON RESSTAT']);
    }
}
