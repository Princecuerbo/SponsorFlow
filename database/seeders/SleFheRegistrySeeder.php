<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class SleFheRegistrySeeder extends Seeder
{
    public const FILENAME = 'SLE-FHE DATA.xlsx';

    public const SHEET_DISTRICTS = ['DISTRICT 1' => 1, 'DISTRICT 2' => 2];

    public const EXPECTED_ROWS = ['DISTRICT 1' => 874, 'DISTRICT 2' => 1058];

    public function run(): void
    {
        throw new RuntimeException(
            'Registry seeding is disabled. Run sponsorflow:registry-preflight for a read-only report. '
            . 'Database-writing registry imports require a separately approved controlled workflow.'
        );
    }

    /** An explicit source override must never silently fall back to another workbook. */
    public static function candidatePaths(): array
    {
        $override = config('sle-fhe.registry_source');

        if (is_string($override) && trim($override) !== '') {
            return [trim($override)];
        }

        return [storage_path('app/' . self::FILENAME), base_path(self::FILENAME)];
    }

    public static function sourceExists(): bool
    {
        foreach (self::candidatePaths() as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }
}
