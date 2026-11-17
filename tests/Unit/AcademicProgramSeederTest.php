<?php

namespace Tests\Unit;

use Database\Seeders\AcademicProgramSeeder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** Catalog planning only; never runs the seeder or connects to a database. */
class AcademicProgramSeederTest extends TestCase
{
    public function test_fresh_definitions_include_reviewed_additions_and_distinct_legacy_degree(): void
    {
        $definitions = AcademicProgramSeeder::missingPrograms([]);
        $this->assertCount(32, $definitions);
        $this->assertCount(32, array_unique(array_column($definitions, 'code')));
        $this->assertCount(32, array_unique(array_column($definitions, 'name')));
        $byCode = array_column($definitions, null, 'code');
        foreach ([
            'BSAM' => 'Bachelor of Science in Agribusiness Management',
            'BSES' => 'Bachelor of Science in Environmental Science',
            'BSHM' => 'Bachelor of Science in Hospitality Management',
            'BSDC' => 'Bachelor of Science in Development Communication',
            'BSM' => 'Bachelor of Science in Mathematics',
            'BITM' => 'Bachelor in Industrial Technology Management',
        ] as $code => $name) {
            $this->assertSame($name, $byCode[$code]['name']);
            $this->assertSame($code, $byCode[$code]['short_name']);
            $this->assertTrue($byCode[$code]['is_undergraduate']);
            $this->assertTrue($byCode[$code]['is_active']);
            $this->assertArrayNotHasKey('program_id', $byCode[$code]);
            $this->assertArrayNotHasKey('is_board_program', $byCode[$code]);
        }
        $this->assertSame('Bachelor of Science in Industrial Technology', $byCode['BSITech']['name']);
        $this->assertSame('BSITech', $byCode['BSITech']['short_name']);
    }

    public function test_repeat_seed_plan_is_empty_and_preserves_reviewed_attributes(): void
    {
        $existing = AcademicProgramSeeder::definitions();
        $existing[0] = [...$existing[0], 'program_id' => 42, 'code' => 'BEED', 'short_name' => 'BEED', 'is_active' => false];
        $before = $existing;
        $this->assertSame([], AcademicProgramSeeder::missingPrograms($existing));
        $this->assertSame($before, $existing);
    }

    public function test_same_degree_with_reviewed_code_is_not_duplicated(): void
    {
        $existing = [['code' => 'REVIEWED', 'name' => ' Bachelor  of Science in Agribusiness Management ']];
        $missing = AcademicProgramSeeder::missingPrograms($existing);
        $this->assertCount(31, $missing);
        $this->assertNotContains('BSAM', array_column($missing, 'code'));
    }

    public function test_conflicting_code_blocks_the_entire_plan(): void
    {
        $this->expectException(RuntimeException::class);
        AcademicProgramSeeder::missingPrograms([['code' => 'bitm', 'name' => 'Bachelor of Science in Industrial Technology']]);
    }

    public function test_existing_duplicate_names_require_review(): void
    {
        $this->expectException(RuntimeException::class);
        AcademicProgramSeeder::missingPrograms([
            ['code' => 'ONE', 'name' => 'Same Degree'],
            ['code' => 'TWO', 'name' => 'same  degree'],
        ]);
    }
}
