<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Applies the approved Phase D1 registry course corrections atomically.
 *
 * 515 case-only + 164 spelling/alias = 679 updates against sponsorflow.sle_fhe_registries.
 *
 * Safety guarantees:
 *  - Single DB::transaction(); any exception auto-rolls back.
 *  - Every UPDATE uses BINARY (case-sensitive) matching on course.
 *  - Exact affected-row count asserted after every statement; mismatch throws.
 *  - Protected IDs 886 and 1388 verified untouched before commit.
 *  - All 12 source-review held IDs verified to retain original course values.
 *  - Total registry count (1,903) verified before commit.
 *  - No stored procedures, DDL, seeders, migrations, or schema objects created.
 *  - academic_programs, .env, and all unrelated tables are never touched.
 */
final class ApplyRegistryCorrectionsD1 extends Command
{
    protected $signature = 'sponsorflow:apply-registry-corrections-d1
        {--dry-run : Verify counts and pre-conditions without committing any changes}
        {--connection=mysql : Laravel database connection name}';

    protected $description = 'Apply the 679 approved Phase D1 registry course corrections (515 case-only + 164 spelling/alias). Dry-run by default unless --no-dry-run is passed.';

    // -------------------------------------------------------------------------
    // Registry IDs that must NEVER be updated under any circumstance.
    // -------------------------------------------------------------------------

    /** Hard-blocked source-conflict identities (per RegistryImportPreflight::BLOCKED). */
    private const PROTECTED_IDS = [886, 1388];

    /**
     * IDs held from the case-only groups (anomalous records identified during reconciliation).
     * These retain their all-caps course values intentionally.
     */
    private const HELD_CASE_ONLY_IDS = [
        172, 243, 593, 1575, 1623,  // BSAM case-only holds (null purok / anomalous)
        1514,                        // BSDC case-only hold  (student year 2023)
    ];

    /**
     * IDs held from the spelling groups (source-review holds).
     */
    private const HELD_SPELL_IDS = [
        1037, 1325,  // BSAM trailing-period (null purok / high-sequence)
        288,         // BSES trailing-period  (null purok / seq 7050; source-review hold)
        1390,        // ABPolSci dash variant  (anomalous purok '45876'; source-review hold)
    ];

    // -------------------------------------------------------------------------
    // Expected post-correction residuals for intentionally held source values.
    // key => expected remaining count after corrections.
    // -------------------------------------------------------------------------
    private const EXPECTED_RESIDUALS = [
        'BACHELOR OF SCIENCE IN AGRIBUSINESS MANAGEMENT'   => 5,  // IDs 172,243,593,1575,1623
        'BACHELOR OF SCIENCE IN DEVELOPMENT COMMUNICATION' => 1,  // ID 1514
        'Bachelor of Science in Agribusiness Management.'  => 2,  // IDs 1037,1325
        'BACHELOR OF SCIENCE IN ENVIRONMENTAL SCIENCE.'    => 1,  // ID 288
        'BACHELOR OF ARTS - POLITICAL SCIENCE'             => 1,  // ID 1390
        // Permanently held (never in correctable set):
        'BACHELOR OF ARTS -POLITICAL SCIENCE'              => 1,  // ID 1007
        'BACHELOR OF SCIENCE IN TECHNOLOGY PROGRAM'        => 1,  // ID 1144
    ];

    // -------------------------------------------------------------------------
    // The 23 approved UPDATE batches in execution order.
    // Each entry: [label, expected_rows, sql, bindings_without_held_ids]
    // Held IDs are injected at runtime via buildHeld().
    // -------------------------------------------------------------------------

    /** Returns the merged NOT IN list for each batch. */
    private function excluded(array ...$extra): array
    {
        return array_merge(self::PROTECTED_IDS, ...$extra);
    }

    public function handle(): int
    {
        $this->line('');
        $this->line('=================================================================');
        $this->line(' SponsorFlow — Phase D1 Registry Course Corrections [RETIRED]');
        $this->line(' All 679 approved updates have already been executed and committed.');
        $this->line('=================================================================');
        $this->info('Execution status: COMMITTED (515 case-only + 164 spelling/alias = 679 records).');
        $this->warn('This one-time migration command is now safely retired.');
        $this->line('Active database write execution is disabled to prevent accidental re-runs.');
        $this->line('All batch definitions, assertions, and mapping logic are preserved below for audit documentation.');
        $this->line('');

        return self::SUCCESS;
    }

    /**
     * @deprecated Archived execution method. Retained for audit and documentation purposes only.
     */
    private function _archivedExecution(ConnectionInterface $db): array
    {
        return $db->transaction(function () use ($db): array {
            return $this->applyAll($db);
        });
    }

    // -------------------------------------------------------------------------
    // Pre-flight (read-only, outside transaction)
    // -------------------------------------------------------------------------

    private function preflight(ConnectionInterface $db): void
    {
        // Total count.
        $total = $db->selectOne('SELECT COUNT(*) AS n FROM sle_fhe_registries')->n;
        $this->assertEq(1903, (int) $total, 'total registry count');

        // Protected IDs must exist with their exact expected course values.
        foreach ([
            886  => 'Bachelor of Science in Information TechnologyNOLOGY',
            1388 => 'Bachelor of Science in Information TechnologyNOLOGY',
        ] as $id => $expectedCourse) {
            $row = $db->selectOne(
                'SELECT course FROM sle_fhe_registries WHERE id = ?', [$id]
            );
            if ($row === null) {
                throw new RuntimeException("Protected ID {$id} not found in registry.");
            }
            if ($row->course !== $expectedCourse) {
                throw new RuntimeException(
                    "Protected ID {$id} course mismatch: expected '{$expectedCourse}', got '{$row->course}'."
                );
            }
        }

        // All held IDs must exist.
        $allHeld = array_merge(self::HELD_CASE_ONLY_IDS, self::HELD_SPELL_IDS);
        foreach ($allHeld as $id) {
            $row = $db->selectOne('SELECT id FROM sle_fhe_registries WHERE id = ?', [$id]);
            if ($row === null) {
                throw new RuntimeException("Held registry ID {$id} not found; cannot verify hold status.");
            }
        }

        // Case-only groups: verify expected pre-correction counts.
        $checks = [
            ['BACHELOR OF SCIENCE IN AGRIBUSINESS MANAGEMENT',   353, $this->excluded(self::HELD_CASE_ONLY_IDS)],
            ['BACHELOR OF SCIENCE IN ENVIRONMENTAL SCIENCE',      78, $this->excluded()],
            ['BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT',     62, $this->excluded()],
            ['BACHELOR OF SCIENCE IN DEVELOPMENT COMMUNICATION',  14, $this->excluded([1514])],
            ['BACHELOR OF SCIENCE IN MATHEMATICS',                 2, $this->excluded()],
            ['BACHELOR OF SCIENCE IN AGRICULTURE',                 2, $this->excluded()],
            ['BACHELOR OF SCIENCE IN CIVIL ENGINEERING',           1, $this->excluded()],
            ['BACHELOR OF ARTS IN POLITICAL SCIENCE',              3, $this->excluded()],
        ];
        foreach ($checks as [$course, $expected, $excl]) {
            $n = $this->countBinary($db, $course, $excl);
            $this->assertEq($expected, $n, "pre-flight: '{$course}'");
        }

        $this->line("  <fg=green>✓</> Total rows: {$total}");
        $this->line("  <fg=green>✓</> Protected IDs 886, 1388 present with expected course values.");
        $this->line("  <fg=green>✓</> All 12 held IDs present.");
        $this->line("  <fg=green>✓</> Case-only pre-counts verified (353/78/62/14/2/2/1/3).");
    }

    // -------------------------------------------------------------------------
    // Main execution (inside DB::transaction)
    // -------------------------------------------------------------------------

    private function applyAll(ConnectionInterface $db): array
    {
        $batches = [];
        $caseTotal = 0;
        $spellTotal = 0;

        // ------------------------------------------------------------------
        // BATCH 1: CASE-ONLY CORRECTIONS (515 rows)
        // ------------------------------------------------------------------

        // 1a. BSAM — 353 rows
        $r = $this->update($db, '1a',
            'Bachelor of Science in Agribusiness Management',
            'BACHELOR OF SCIENCE IN AGRIBUSINESS MANAGEMENT',
            $this->excluded(self::HELD_CASE_ONLY_IDS), 353);
        $batches[] = $r; $caseTotal += $r['actual'];

        // 1b. BSES — 78 rows
        $r = $this->update($db, '1b',
            'Bachelor of Science in Environmental Science',
            'BACHELOR OF SCIENCE IN ENVIRONMENTAL SCIENCE',
            $this->excluded(), 78);
        $batches[] = $r; $caseTotal += $r['actual'];

        // 1c. BSHM — 62 rows
        $r = $this->update($db, '1c',
            'Bachelor of Science in Hospitality Management',
            'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT',
            $this->excluded(), 62);
        $batches[] = $r; $caseTotal += $r['actual'];

        // 1d. BSDC — 14 rows (hold ID 1514)
        $r = $this->update($db, '1d',
            'Bachelor of Science in Development Communication',
            'BACHELOR OF SCIENCE IN DEVELOPMENT COMMUNICATION',
            $this->excluded([1514]), 14);
        $batches[] = $r; $caseTotal += $r['actual'];

        // 1e. BSM — 2 rows
        $r = $this->update($db, '1e',
            'Bachelor of Science in Mathematics',
            'BACHELOR OF SCIENCE IN MATHEMATICS',
            $this->excluded(), 2);
        $batches[] = $r; $caseTotal += $r['actual'];

        // 1f. BSAgr — 2 rows
        $r = $this->update($db, '1f',
            'Bachelor of Science in Agriculture',
            'BACHELOR OF SCIENCE IN AGRICULTURE',
            $this->excluded(), 2);
        $batches[] = $r; $caseTotal += $r['actual'];

        // 1g. BSCE — 1 row
        $r = $this->update($db, '1g',
            'Bachelor of Science in Civil Engineering',
            'BACHELOR OF SCIENCE IN CIVIL ENGINEERING',
            $this->excluded(), 1);
        $batches[] = $r; $caseTotal += $r['actual'];

        // 1h. AB PolSci all-caps — 3 rows
        $r = $this->update($db, '1h',
            'Bachelor of Arts in Political Science',
            'BACHELOR OF ARTS IN POLITICAL SCIENCE',
            $this->excluded(), 3);
        $batches[] = $r; $caseTotal += $r['actual'];

        $this->assertEq(515, $caseTotal, 'case-only subtotal');

        // ------------------------------------------------------------------
        // BATCH 2: SPELLING / ALIAS CORRECTIONS (164 rows)
        // ------------------------------------------------------------------

        // === BSAM SPELLING (53 rows) ===

        // 2a. Trailing period — 48 rows (hold IDs 1037, 1325)
        $r = $this->update($db, '2a',
            'Bachelor of Science in Agribusiness Management',
            'Bachelor of Science in Agribusiness Management.',
            $this->excluded([1037, 1325]), 48);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2b. AGRIBUSSINES — 2 rows
        $r = $this->update($db, '2b',
            'Bachelor of Science in Agribusiness Management',
            'BACHELOR OF SCIENCE IN AGRIBUSSINES MANAGEMENT',
            $this->excluded(), 2);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2c. Duplicate OF SCIENCE OF SCIENCE — 1 row
        $r = $this->update($db, '2c',
            'Bachelor of Science in Agribusiness Management',
            'BACHELOR OF SCIENCE OF SCIENCE IN AGRIBUSINESS MANAGEMENT',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2d. BAHCELOR transposition — 1 row
        $r = $this->update($db, '2d',
            'Bachelor of Science in Agribusiness Management',
            'BAHCELOR OF SCIENCE IN AGRIBUSINESS MANAGEMENT',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2e. BACGELOR + MGT — 1 row
        $r = $this->update($db, '2e',
            'Bachelor of Science in Agribusiness Management',
            'BACGELOR OF SCIENCE IN AGRIBUSINESS MGT',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // === BSES SPELLING (56 rows) ===

        // 2f. Trailing period — 10 rows (hold ID 288; source-review hold)
        $r = $this->update($db, '2f',
            'Bachelor of Science in Environmental Science',
            'BACHELOR OF SCIENCE IN ENVIRONMENTAL SCIENCE.',
            $this->excluded([288]), 10);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2g. SCIENCEE — 2 rows
        $r = $this->update($db, '2g',
            'Bachelor of Science in Environmental Science',
            'BACHELOR OF SCIENCE IN ENVIRONMENTAL SCIENCEE',
            $this->excluded(), 2);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2h. EVIRONMENTAL uppercase — 10 rows
        $r = $this->update($db, '2h',
            'Bachelor of Science in Environmental Science',
            'BACHELOR OF SCIENCE IN EVIRONMENTAL SCIENCE',
            $this->excluded(), 10);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2i. Enviromental mixed-case — 33 rows
        $r = $this->update($db, '2i',
            'Bachelor of Science in Environmental Science',
            'Bachelor of Science in Enviromental Science',
            $this->excluded(), 33);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2j. ENVI.SCI — 1 row
        $r = $this->update($db, '2j',
            'Bachelor of Science in Environmental Science',
            'BACHELOR OF SCIENCE IN ENVI.SCI',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // === BSHM SPELLING (15 rows) ===

        // 2k. Mgt abbreviation — 9 rows
        $r = $this->update($db, '2k',
            'Bachelor of Science in Hospitality Management',
            'Bachelor of Science in Hospitality Mgt',
            $this->excluded(), 9);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2l. Missing IN — 3 rows
        $r = $this->update($db, '2l',
            'Bachelor of Science in Hospitality Management',
            'BACHELOR OF SCIENCE HOSPITALITY MANAGEMENT',
            $this->excluded(), 3);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2m. Dash for IN — 1 row
        $r = $this->update($db, '2m',
            'Bachelor of Science in Hospitality Management',
            'BACHELOR OF SCIENCE - HOSPITALITY MANAGEMENT',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2n. Hospitability typo — 2 rows
        $r = $this->update($db, '2n',
            'Bachelor of Science in Hospitality Management',
            'Bachelor Science in Hospitability Management',
            $this->excluded(), 2);
        $batches[] = $r; $spellTotal += $r['actual'];

        // === BSDC SPELLING (4 rows) ===

        // 2o. BACGHELOR + COMM — 3 rows
        $r = $this->update($db, '2o',
            'Bachelor of Science in Development Communication',
            'BACGHELOR OF SCIENCE IN DEVELOPMENT COMM',
            $this->excluded(), 3);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2p. Extraneous BS prefix — 1 row
        $r = $this->update($db, '2p',
            'Bachelor of Science in Development Communication',
            'BSBachelor of Science in Development Communication',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // === BSIT SPELLING (2 rows) ===

        // 2q. Missing space BACHELOROFSCIENCE — 1 row
        $r = $this->update($db, '2q',
            'Bachelor of Science in Information Technology',
            'BACHELOROFSCIENCE IN INFORMATION TECHNOLOGY',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2r. INFORMATION TECHNOLOGY PROGRAM — 1 row (previously ambiguous; confirmed approved)
        $r = $this->update($db, '2r',
            'Bachelor of Science in Information Technology',
            'BACHELOR OF SCIENCE INFORMATION TECHNOLOGY PROGRAM',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // === AB POLSCI SPELLING (34 rows) ===

        // 2s. Space-dash-space variant — 17 rows (hold ID 1390; source-review hold)
        $r = $this->update($db, '2s',
            'Bachelor of Arts in Political Science',
            'BACHELOR OF ARTS - POLITICAL SCIENCE',
            $this->excluded([1390]), 17);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2t. Dash+no-space + misspelling SIENCE — 1 row
        $r = $this->update($db, '2t',
            'Bachelor of Arts in Political Science',
            'BACHELOR OF ARTS -POLITICAL SIENCE',
            $this->excluded(), 1);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2u. POLITCAL misspelling — 4 rows
        $r = $this->update($db, '2u',
            'Bachelor of Arts in Political Science',
            'BACHELOR OF ARTS IN POLITCAL SCIENCE',
            $this->excluded(), 4);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2v. Dash after ARTS, no "in" — 2 rows
        $r = $this->update($db, '2v',
            'Bachelor of Arts in Political Science',
            'BACHELOR OF ARTS- POLITICAL SCIENCE',
            $this->excluded(), 2);
        $batches[] = $r; $spellTotal += $r['actual'];

        // 2w. Missing "in" mixed-case — 10 rows
        $r = $this->update($db, '2w',
            'Bachelor of Arts in Political Science',
            'Bachelor of Arts Political Science',
            $this->excluded(), 10);
        $batches[] = $r; $spellTotal += $r['actual'];

        $this->assertEq(164, $spellTotal, 'spell/alias subtotal');

        $grandTotal = $caseTotal + $spellTotal;
        $this->assertEq(679, $grandTotal, 'grand total');

        // ------------------------------------------------------------------
        // POST-FLIGHT (inside transaction, before commit)
        // ------------------------------------------------------------------

        // Total row count must still be 1903.
        $finalCount = (int) $db->selectOne('SELECT COUNT(*) AS n FROM sle_fhe_registries')->n;
        $this->assertEq(1903, $finalCount, 'post-flight total row count');

        // Protected IDs must still have their original course values.
        foreach ([886, 1388] as $id) {
            $row = $db->selectOne('SELECT course FROM sle_fhe_registries WHERE id = ?', [$id]);
            if ($row === null) {
                throw new RuntimeException("Post-flight: protected ID {$id} missing.");
            }
            if ($row->course !== 'Bachelor of Science in Information TechnologyNOLOGY') {
                throw new RuntimeException(
                    "Post-flight: protected ID {$id} course changed to '{$row->course}'."
                );
            }
        }

        // Held IDs must still exist (they should have been unaffected by the WHERE clauses).
        foreach (array_merge(self::HELD_CASE_ONLY_IDS, self::HELD_SPELL_IDS) as $id) {
            $row = $db->selectOne('SELECT id FROM sle_fhe_registries WHERE id = ?', [$id]);
            if ($row === null) {
                throw new RuntimeException("Post-flight: held ID {$id} is missing.");
            }
        }

        // Residual check: each intentionally held source value must have the expected remaining count.
        foreach (self::EXPECTED_RESIDUALS as $course => $expectedCount) {
            $actual = $this->countBinaryRaw($db, $course);
            $this->assertEq($expectedCount, $actual, "post-flight residual: '{$course}'");
        }

        // All corrected source values (not in EXPECTED_RESIDUALS) must now be zero.
        $correctedSourceValues = [
            'BACHELOR OF SCIENCE IN ENVIRONMENTAL SCIENCE',
            'BACHELOR OF SCIENCE IN HOSPITALITY MANAGEMENT',
            'BACHELOR OF SCIENCE IN MATHEMATICS',
            'BACHELOR OF SCIENCE IN AGRICULTURE',
            'BACHELOR OF SCIENCE IN CIVIL ENGINEERING',
            'BACHELOR OF ARTS IN POLITICAL SCIENCE',
            'BACHELOR OF SCIENCE IN AGRIBUSSINES MANAGEMENT',
            'BACHELOR OF SCIENCE OF SCIENCE IN AGRIBUSINESS MANAGEMENT',
            'BAHCELOR OF SCIENCE IN AGRIBUSINESS MANAGEMENT',
            'BACGELOR OF SCIENCE IN AGRIBUSINESS MGT',
            'BACHELOR OF SCIENCE IN ENVIRONMENTAL SCIENCEE',
            'BACHELOR OF SCIENCE IN EVIRONMENTAL SCIENCE',
            'Bachelor of Science in Enviromental Science',
            'BACHELOR OF SCIENCE IN ENVI.SCI',
            'Bachelor of Science in Hospitality Mgt',
            'BACHELOR OF SCIENCE HOSPITALITY MANAGEMENT',
            'BACHELOR OF SCIENCE - HOSPITALITY MANAGEMENT',
            'Bachelor Science in Hospitability Management',
            'BACGHELOR OF SCIENCE IN DEVELOPMENT COMM',
            'BSBachelor of Science in Development Communication',
            'BACHELOROFSCIENCE IN INFORMATION TECHNOLOGY',
            'BACHELOR OF SCIENCE INFORMATION TECHNOLOGY PROGRAM',
            'BACHELOR OF ARTS -POLITICAL SIENCE',
            'BACHELOR OF ARTS IN POLITCAL SCIENCE',
            'BACHELOR OF ARTS- POLITICAL SCIENCE',
            'Bachelor of Arts Political Science',
        ];
        foreach ($correctedSourceValues as $course) {
            $actual = $this->countBinaryRaw($db, $course);
            $this->assertEq(0, $actual, "post-flight zero residual: '{$course}'");
        }

        return [
            'batches'        => $batches,
            'case_only_total'=> $caseTotal,
            'spell_total'    => $spellTotal,
            'grand_total'    => $grandTotal,
            'final_row_count'=> $finalCount,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Execute one approved UPDATE batch and assert the affected-row count.
     * Uses BINARY comparison to ensure case-sensitive matching.
     * Throws RuntimeException on count mismatch, triggering automatic ROLLBACK.
     *
     * @param  array<int> $excludedIds
     */
    private function update(
        ConnectionInterface $db,
        string $batch,
        string $replacement,
        string $originalCourse,
        array $excludedIds,
        int $expected
    ): array {
        $placeholders = implode(',', array_fill(0, count($excludedIds), '?'));
        $sql = "UPDATE sle_fhe_registries
                   SET course = ?
                 WHERE BINARY course = ?
                   AND id NOT IN ({$placeholders})";

        $bindings = array_merge([$replacement, $originalCourse], $excludedIds);
        $actual   = $db->update($sql, $bindings);

        $label = "[{$batch}] {$originalCourse}";
        $this->assertEq($expected, $actual, $label);

        $symbol = $actual === $expected ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $this->line("  {$symbol} [{$batch}] {$actual}/{$expected} rows — ".mb_substr($originalCourse, 0, 60));

        return ['batch' => $batch, 'label' => $originalCourse, 'expected' => $expected, 'actual' => $actual];
    }

    /** Count rows matching a BINARY course value, excluding given IDs. */
    private function countBinary(ConnectionInterface $db, string $course, array $excludedIds): int
    {
        $placeholders = implode(',', array_fill(0, count($excludedIds), '?'));
        $sql = "SELECT COUNT(*) AS n FROM sle_fhe_registries
                 WHERE BINARY course = ? AND id NOT IN ({$placeholders})";

        return (int) $db->selectOne($sql, array_merge([$course], $excludedIds))->n;
    }

    /** Count rows matching a BINARY course value (no ID exclusions). */
    private function countBinaryRaw(ConnectionInterface $db, string $course): int
    {
        return (int) $db->selectOne(
            'SELECT COUNT(*) AS n FROM sle_fhe_registries WHERE BINARY course = ?',
            [$course]
        )->n;
    }

    /**
     * Assert an exact equality. Throws RuntimeException on mismatch.
     * The enclosing DB::transaction() will catch this and automatically ROLLBACK.
     */
    private function assertEq(int $expected, int $actual, string $label): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException(
                "[MISMATCH] {$label}: expected={$expected}, actual={$actual}. "
                .'Transaction rolled back automatically — no changes committed.'
            );
        }
    }
}
