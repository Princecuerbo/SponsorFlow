<?php

namespace Tests\Unit;

use App\Support\SleFhe\RegistryNormalizer;
use App\Support\Spreadsheet\XlsxReader;
use PHPUnit\Framework\TestCase;
use Tests\Support\MiniXlsx;

class SleFheRegistrySupportTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = (string) tempnam(sys_get_temp_dir(), 'xlsx-');
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_reader_lists_sheets_and_resolves_cells(): void
    {
        MiniXlsx::write($this->path, [
            'DISTRICT 1' => [
                ['A' => 'title'],
                ['B' => 'SEX'],
                ['A' => '1', 'B' => '2025-1', 'C' => 'M'],
            ],
        ], 'Summary');

        $reader = XlsxReader::open($this->path);

        $this->assertSame(['Summary', 'DISTRICT 1'], $reader->sheetNames());
        $this->assertSame([], $reader->rows('Summary'));

        $rows = $reader->rows('DISTRICT 1');
        $this->assertSame([1, 2, 3], array_keys($rows));
        $this->assertSame(['A' => 'title'], $rows[1]);
        $this->assertSame(['B' => 'SEX'], $rows[2]);
        $this->assertSame(['A' => '1', 'B' => '2025-1', 'C' => 'M'], $rows[3]);
    }

    public function test_reader_rejects_a_file_that_is_not_a_zip_container(): void
    {
        file_put_contents($this->path, 'this is not a spreadsheet');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not a valid .xlsx file');

        XlsxReader::open($this->path);
    }

    public function test_reader_reports_a_missing_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Spreadsheet not found');

        XlsxReader::open($this->path . '-does-not-exist');
    }

    public function test_reader_rejects_an_unknown_sheet_name(): void
    {
        MiniXlsx::write($this->path, ['DISTRICT 1' => [['A' => 'x']]]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Worksheet 'DISTRICT 9' was not found");

        XlsxReader::open($this->path)->rows('DISTRICT 9');
    }

    public function test_student_ids_are_normalized_and_validated(): void
    {
        $this->assertSame('2025-1001', RegistryNormalizer::studentId(' 2025-1001 '));
        $this->assertSame('2025-1001', RegistryNormalizer::studentId("2025-1001\t"));
        $this->assertSame('2024-12345', RegistryNormalizer::studentId('2024-12345'));

        $this->assertNull(RegistryNormalizer::studentId('LAGBAWAN'));
        $this->assertNull(RegistryNormalizer::studentId(''));
        $this->assertNull(RegistryNormalizer::studentId(null));
        $this->assertNull(RegistryNormalizer::studentId('2025-1'));
        $this->assertNull(RegistryNormalizer::studentId('2025-1001A'));
    }

    public function test_sex_civil_status_and_campus_fold_onto_canonical_values(): void
    {
        foreach (['F', 'FEMALE', 'Female', 'female', 'Felale'] as $input) {
            $this->assertSame('Female', RegistryNormalizer::sex($input), $input);
        }

        foreach (['M', 'MALE', 'Male'] as $input) {
            $this->assertSame('Male', RegistryNormalizer::sex($input), $input);
        }

        $this->assertNull(RegistryNormalizer::sex('  '));

        $this->assertSame('Single', RegistryNormalizer::civilStatus('SINGLE'));
        $this->assertSame('Married', RegistryNormalizer::civilStatus('Married'));

        $this->assertSame('DOrSU Main Campus', RegistryNormalizer::campus('DAVAO ORIENTAL STATE UNIVERSITY-MAIN CAMPUS'));
        $this->assertSame('DOrSU Main Campus', RegistryNormalizer::campus('DAVAO ORIENTAL STATE UNIVERSITY - MAIN CAMPUS'));
        $this->assertSame('DOrSU Main Campus', RegistryNormalizer::campus('Davao Oriental State University'));
        $this->assertSame('DOrSU Cateel Extension Campus', RegistryNormalizer::campus('DOrSU-CATEEL EXTENTION CAMPUS'));
        $this->assertSame('DOrSU Baganga Extension Campus', RegistryNormalizer::campus('DOrsU-BAGANGA EXTENSION CAMPUS'));
        $this->assertSame('DOrSU Banaybanay Extension Campus', RegistryNormalizer::campus('DAVAO ORIENTAL STATE UNIVERSITY BANAYBANAY EXTENSION CAMPUS'));
        $this->assertSame('DOrSU San Isidro Campus', RegistryNormalizer::campus('SAN ISIDRO CAMPUS'));
    }

    public function test_municipalities_map_onto_lgu_names_and_flag_out_of_province_rows(): void
    {
        $this->assertSame('Mati City', RegistryNormalizer::municipality('MATI CTY'));
        $this->assertSame('Mati City', RegistryNormalizer::municipality('MATI'));
        $this->assertSame('Mati City', RegistryNormalizer::municipality('MATI CITY'));
        $this->assertSame('Banaybanay', RegistryNormalizer::municipality('BANAY BANAY'));
        $this->assertSame('Banaybanay', RegistryNormalizer::municipality('BANAY-BANAY'));
        $this->assertSame('Tarragona', RegistryNormalizer::municipality('Tarragona'));
        $this->assertSame('Governor Generoso', RegistryNormalizer::municipality('GOVERNOR GENEROSO'));

        foreach (RegistryNormalizer::DAVAO_ORIENTAL_MUNICIPALITIES as $lgu) {
            $this->assertTrue(RegistryNormalizer::isDavaoOrientalMunicipality($lgu), $lgu);
            $this->assertTrue(
                RegistryNormalizer::isDavaoOrientalMunicipality(RegistryNormalizer::municipality(strtoupper($lgu))),
                strtoupper($lgu)
            );
        }

        $this->assertSame('Lupon', RegistryNormalizer::municipality('LUPON'));
        $this->assertFalse(RegistryNormalizer::isDavaoOrientalMunicipality(RegistryNormalizer::municipality('LUPON')));
        $this->assertFalse(RegistryNormalizer::isDavaoOrientalMunicipality(RegistryNormalizer::municipality('BOSTON')));
        $this->assertFalse(RegistryNormalizer::isDavaoOrientalMunicipality(null));
    }

    public function test_text_collapses_internal_whitespace(): void
    {
        $this->assertSame('Purok 2', RegistryNormalizer::text("  Purok   2 \n"));
        $this->assertNull(RegistryNormalizer::text('   '));
        $this->assertNull(RegistryNormalizer::text(null));
    }
}
