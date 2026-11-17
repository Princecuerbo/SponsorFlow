<?php

namespace Tests\Unit;

use App\Console\Commands\ImportSleFheRegistryCommand;
use PHPUnit\Framework\TestCase;

class ImportSleFheRegistryCommandTest extends TestCase
{
    public function test_expected_record_count_is_1903(): void
    {
        $this->assertSame(1903, ImportSleFheRegistryCommand::EXPECTED_RECORD_COUNT);
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

        $this->assertSame($expected, ImportSleFheRegistryCommand::EXPECTED_COLUMNS);
    }
}
