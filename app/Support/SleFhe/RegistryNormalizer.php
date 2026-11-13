<?php

namespace App\Support\SleFhe;

/**
 * Canonicalises the raw SLE-FHE masterlist values.
 *
 * The government workbook is hand-maintained, so the same fact is spelled several
 * ways: sex appears as F / FEMALE / Female (plus the typo "FELALE"), the same campus
 * is written eight different ways, and municipality names mix ALL CAPS with title
 * case. Matching on the raw text would fragment every downstream filter, so the
 * ingest folds them onto one canonical value each.
 */
class RegistryNormalizer
{
    /**
     * The 11 municipalities of Davao Oriental, per the official LGU list.
     *
     * @var list<string>
     */
    public const DAVAO_ORIENTAL_MUNICIPALITIES = [
        'Mati City',
        'Banaybanay',
        'Baganga',
        'Caraga',
        'Cateel',
        'Governor Generoso',
        'Guilian',
        'Manay',
        'San Isidro',
        'San Luis',
        'Tarragona',
    ];

    /**
     * The six campuses carried on the workbook's own "Summary" sheet.
     *
     * @var list<string>
     */
    public const CANONICAL_CAMPUSES = [
        'DOrSU Main Campus',
        'DOrSU Cateel Extension Campus',
        'DOrSU Baganga Extension Campus',
        'DOrSU Tarragona Extension Campus',
        'DOrSU Banaybanay Extension Campus',
        'DOrSU San Isidro Campus',
    ];

    /** @var array<string, string> */
    private const SEX = [
        'M' => 'Male',
        'MALE' => 'Male',
        'F' => 'Female',
        'FEMALE' => 'Female',
        'FELALE' => 'Female', // misspelling present in the source
    ];

    /** @var array<string, string> */
    private const CIVIL_STATUS = [
        'SINGLE' => 'Single',
        'MARRIED' => 'Married',
        'WIDOWED' => 'Widowed',
    ];

    /**
     * Municipality spellings that must fold onto an LGU name.
     *
     * @var array<string, string>
     */
    private const MUNICIPALITY = [
        'MATI' => 'Mati City',
        'MATI CITY' => 'Mati City',
        'MATI CTY' => 'Mati City',
        'MATI CITY D' => 'Mati City',
        'BANAY BANAY' => 'Banaybanay',
        'BANAY-BANAY' => 'Banaybanay',
        'BANAYBANAY' => 'Banaybanay',
        'CATEEL' => 'Cateel',
        'GOVERNOR GENEROSO' => 'Governor Generoso',
    ];

    /**
     * Campus detection keys, checked before the Main Campus fallback because the
     * Main Campus strings are the only ones without a location token.
     *
     * @var array<string, string>
     */
    private const CAMPUS = [
        'CATEEL' => 'DOrSU Cateel Extension Campus',
        'BAGANGA' => 'DOrSU Baganga Extension Campus',
        'TARRAGONA' => 'DOrSU Tarragona Extension Campus',
        'BANAYBANAY' => 'DOrSU Banaybanay Extension Campus',
        'BANAY BANAY' => 'DOrSU Banaybanay Extension Campus',
        'BANAY-BANAY' => 'DOrSU Banaybanay Extension Campus',
        'SAN ISIDRO' => 'DOrSU San Isidro Campus',
    ];

    /**
     * Collapse runs of whitespace and trim.
     */
    public static function text(?string $value): ?string
    {
        $collapsed = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return $collapsed === '' ? null : $collapsed;
    }

    /**
     * A registry row is only usable as a match key with a well-formed student ID.
     * Everything else (blank, a stray municipality name, free text) is rejected so it
     * can never be written to the uniquely indexed student_id column.
     */
    public static function studentId(?string $value): ?string
    {
        $candidate = self::text($value);

        if ($candidate === null) {
            return null;
        }

        $candidate = (string) preg_replace('/\s+/', '', $candidate);

        return preg_match('/^\d{4}-\d{3,5}$/', $candidate) === 1 ? $candidate : null;
    }

    public static function sex(?string $value): ?string
    {
        $normalized = self::text($value);

        return $normalized === null ? null : (self::SEX[strtoupper($normalized)] ?? self::titleCase($normalized));
    }

    public static function civilStatus(?string $value): ?string
    {
        $normalized = self::text($value);

        return $normalized === null ? null : (self::CIVIL_STATUS[strtoupper($normalized)] ?? self::titleCase($normalized));
    }

    public static function municipality(?string $value): ?string
    {
        $normalized = self::text($value);

        if ($normalized === null) {
            return null;
        }

        $key = strtoupper($normalized);

        if (isset(self::MUNICIPALITY[$key])) {
            return self::MUNICIPALITY[$key];
        }

        $folded = self::titleCase($key);

        foreach (self::DAVAO_ORIENTAL_MUNICIPALITIES as $lgu) {
            if (strcasecmp($folded, $lgu) === 0) {
                return $lgu;
            }
        }

        // Outside Davao Oriental (e.g. Lupon, Boston). Kept deliberately: DOrSU serves
        // neighbouring municipalities too, and the seeder reports these separately.
        return $folded;
    }

    public static function isDavaoOrientalMunicipality(?string $municipality): bool
    {
        if ($municipality === null) {
            return false;
        }

        foreach (self::DAVAO_ORIENTAL_MUNICIPALITIES as $lgu) {
            if (strcasecmp($municipality, $lgu) === 0) {
                return true;
            }
        }

        return false;
    }

    public static function campus(?string $value): ?string
    {
        $normalized = self::text($value);

        if ($normalized === null) {
            return null;
        }

        $key = strtoupper($normalized);

        foreach (self::CAMPUS as $needle => $canonical) {
            if (str_contains($key, $needle)) {
                return $canonical;
            }
        }

        return 'DOrSU Main Campus';
    }

    public static function yearLevel(?string $value): ?string
    {
        return self::text($value);
    }

    private static function titleCase(string $value): string
    {
        return ucwords(strtolower($value));
    }
}
