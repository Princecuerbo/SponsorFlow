<?php

namespace App\Support\SleFhe;

use PDO;
use RuntimeException;

/** Only SELECTs inside a server-enforced read-only transaction; never Eloquent or seeders. */
class RegistryPreflightSnapshot
{
    public function read(PDO $pdo): array
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql' || $pdo->inTransaction()) {
            throw new RuntimeException('Preflight requires a fresh MySQL/MariaDB connection with no active transaction.');
        }

        // Fail closed on unsupported servers, including drivers configured for silent errors.
        foreach (['SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', 'SET TRANSACTION READ ONLY'] as $sql) {
            if ($pdo->exec($sql) === false) {
                throw new RuntimeException('Cannot establish read-only snapshot protections.');
            }
        }
        if (! $pdo->beginTransaction()) {
            throw new RuntimeException('Cannot start read-only preflight transaction.');
        }

        try {
            $identity = $this->select($pdo, 'SELECT DATABASE() AS database_name');
            $catalog = $this->select($pdo, 'SELECT program_id, code, name, short_name, is_board_program, is_undergraduate, is_active, created_at, updated_at FROM academic_programs ORDER BY program_id');
            $registry = $this->select($pdo, 'SELECT id, student_id, sex, civil_status, purok, barangay, municipality, course, year_level, campus, district, is_verified, created_at, updated_at FROM sle_fhe_registries ORDER BY id');

            return ['database' => $identity[0]['database_name'] ?? null, 'catalog' => $catalog, 'registry' => $registry];
        } finally {
            if (! $pdo->rollBack()) {
                throw new RuntimeException('Preflight could not close its read-only transaction.');
            }
        }
    }

    private function select(PDO $pdo, string $sql): array
    {
        $statement = $pdo->query($sql);
        if ($statement === false) {
            throw new RuntimeException('Preflight snapshot SELECT failed. No complete report is available.');
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
