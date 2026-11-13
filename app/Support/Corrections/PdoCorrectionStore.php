<?php

namespace App\Support\Corrections;

use Closure;
use PDO;
use Throwable;

/** Narrow SQL allowlist. No Eloquent events, imports, seeders, DDL, or profile writes. */
final class PdoCorrectionStore implements CorrectionStore
{
    private bool $writable = false;
    private bool $active = false;
    private bool $writeSchemaChecked = false;
    private string $catalogCollation;
    private const CATALOG = 'program_id, code, name, short_name, is_board_program, is_undergraduate, is_active, created_at, updated_at';

    public function __construct(private PDO $pdo, private array $connection, private bool $protected)
    {
        CorrectionEvidence::require($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            && ! $pdo->inTransaction(), 'Fresh MariaDB PDO connection required.');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    }

    private function select(string $sql, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function identity(): array
    {
        $row = $this->select('SELECT DATABASE() AS db, @@hostname AS hostname, @@server_id AS server_id, @@port AS port, CURRENT_USER() AS account, VERSION() AS version, @@init_connect AS init_connect')[0];
        CorrectionEvidence::require(str_contains($row['version'], 'MariaDB') && $row['init_connect'] === '', 'MariaDB with no server init_connect commands required.');
        CorrectionEvidence::require((string) $row['port'] === (string) $this->connection['port']
            && $row['db'] === $this->connection['database'], 'Effective connection identity differs from requested configuration.');

        return ['account' => $row['account'], 'database' => $row['db'], 'host' => $this->connection['host'],
            'port' => (string) $row['port'], 'server_hostname' => $row['hostname'], 'server_id' => (string) $row['server_id']];
    }

    /** No global grants, roles, wildcard database privileges or original-db access in tests. */
    public function assertTestGrants(): void
    {
        $identity = $this->identity();
        CorrectionEvidence::require(preg_match('/^sponsorflow_(?:test|correction_test)(?:_[a-z0-9]+)*$/D', $identity['database']) === 1
            && ! str_starts_with(strtolower($identity['account']), 'root@'), 'Restricted isolated-test account required.');
        $version = $this->select('SELECT VERSION() AS version')[0]['version'];
        // This grant proof is deliberately bounded to the application's MariaDB 10.4 baseline.
        // Later versions (e.g. implicit PUBLIC privileges) need a separately reviewed proof.
        CorrectionEvidence::require(preg_match('/^10\.4\.\d+-MariaDB/', $version) === 1,
            'Isolated privilege verification currently supports MariaDB 10.4 only.');
        $grants = $this->select('SHOW GRANTS FOR CURRENT_USER');
        foreach ($grants as $row) {
            $grant = array_values($row)[0];
            // Reject role inheritance and grants that cannot be independently scoped.
            if (str_starts_with($grant, 'GRANT USAGE ON *.* TO ')) {
                continue;
            }
            $scope = self::grantScope($identity['database']);
            CorrectionEvidence::require(preg_match('/^GRANT (.+) ON (.+) TO /', $grant, $match) === 1
                && $match[2] === $scope && ! str_contains($grant, 'WITH GRANT OPTION'), 'Test credentials have broad, inherited or non-test privileges.');
        }
    }

    private static function grantScope(string $database): string
    {
        // MariaDB database GRANT patterns treat unescaped "_" and "%" as wildcards.
        return '`'.strtr($database, ['\\' => '\\\\', '_' => '\\_', '%' => '\\%', '`' => '``']).'`.*';
    }

    private function schema(bool $write): void
    {
        if ($write) {
            $canInspectTriggers = false;
            $databaseScope = self::grantScope($this->identity()['database']);
            foreach ($this->select('SHOW GRANTS FOR CURRENT_USER') as $row) {
                $grant = array_values($row)[0];
                if (preg_match('/^GRANT (.+) ON (.+) TO /', $grant, $match)
                    && in_array($match[2], ['*.*', $databaseScope], true)
                    && ($match[1] === 'ALL PRIVILEGES' || in_array('TRIGGER', explode(', ', $match[1]), true))) {
                    $canInspectTriggers = true;
                }
            }
            CorrectionEvidence::require($canInspectTriggers, 'Cannot independently establish visibility of table triggers.');
        }
        $tables = $this->select("SELECT TABLE_NAME, ENGINE, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('academic_programs','sle_fhe_registries','audit_logs')");
        CorrectionEvidence::require(count($tables) === 3, 'Required correction/audit tables missing.');
        foreach ($tables as $table) {
            CorrectionEvidence::require($table['ENGINE'] === 'InnoDB' && $table['TABLE_TYPE'] === 'BASE TABLE', 'All correction/audit tables must be transactional InnoDB base tables.');
        }
        $required = [
            'academic_programs' => explode(', ', self::CATALOG),
            'sle_fhe_registries' => CorrectionEvidence::REGISTRY_FIELDS,
            'audit_logs' => ['id', 'user_id', 'action', 'target_module', 'details', 'ip_address', 'user_agent', 'role', 'created_at', 'updated_at'],
        ];
        foreach ($required as $table => $fields) {
            $columns = $this->select('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$table]);
            CorrectionEvidence::require(array_diff($fields, array_column($columns, 'COLUMN_NAME')) === [], 'Required columns missing; schema changes are not performed by the executor.');
        }
        $indexes = $this->select("SELECT TABLE_NAME,COLUMN_NAME,NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND INDEX_NAME='PRIMARY' AND TABLE_NAME IN ('academic_programs','sle_fhe_registries','audit_logs')");
        $keys = array_column($indexes, 'COLUMN_NAME', 'TABLE_NAME');
        CorrectionEvidence::require(count($indexes) === 3 && ($keys['academic_programs'] ?? null) === 'program_id'
            && ($keys['sle_fhe_registries'] ?? null) === 'id' && ($keys['audit_logs'] ?? null) === 'id', 'Unexpected primary keys.');
        $collations = $this->select("SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='academic_programs' AND COLUMN_NAME IN ('code','name','short_name')");
        $names = array_unique(array_column($collations, 'COLLATION_NAME'));
        CorrectionEvidence::require(count($collations) === 3 && count($names) === 1
            && preg_match('/^utf8mb4_[a-z0-9_]+$/D', $names[0]) === 1, 'Catalog text collations require explicit compatibility review.');
        $this->catalogCollation = $names[0];
        foreach ($this->select("SHOW VARIABLES LIKE 'innodb_locks_unsafe_for_binlog'") as $variable) {
            CorrectionEvidence::require(in_array(strtoupper((string) $variable['Value']), ['OFF', '0'], true), 'Gap locking must remain enabled.');
        }
        $triggers = $this->select("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE IN ('academic_programs','sle_fhe_registries','audit_logs')");
        CorrectionEvidence::require($triggers === [], 'Unreviewed table triggers prevent correction execution.');
        $references = $this->select("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME='sle_fhe_registries' AND REFERENCED_COLUMN_NAME IN ('course','updated_at')");
        CorrectionEvidence::require($references === [], 'Unexpected foreign-key reference to mutable registry text/timestamp; review required.');
    }

    public function transaction(bool $write, Closure $work): mixed
    {
        CorrectionEvidence::require(! $this->active && ! $this->pdo->inTransaction(), 'Nested correction execution is forbidden.');
        $identity = $this->identity();
        $this->schema($write);
        if ($write && ! $this->protected) {
            $this->assertTestGrants();
        }
        $lock = 'sf-correction-'.substr(hash('sha256', $identity['database']), 0, 40);
        $locked = false;
        try {
            if ($write) {
                $locked = (int) $this->select('SELECT GET_LOCK(?, 0) AS acquired', [$lock])[0]['acquired'] === 1;
                CorrectionEvidence::require($locked, 'Another correction operation holds the database maintenance lock.');
            }
            // Phase A timestamps use UTC+08:00. These SETs are session/transaction only.
            $this->pdo->exec("SET time_zone = '+08:00'");
            $this->pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
            $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->pdo->exec($write ? 'SET TRANSACTION READ WRITE' : 'SET TRANSACTION READ ONLY');
            $this->pdo->beginTransaction();
            $this->active = true;
            $this->writable = $write;
            $result = $work();
            if ($write) {
                $this->pdo->commit();
            } else {
                $this->pdo->rollBack();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } finally {
            $this->active = $this->writable = $this->writeSchemaChecked = false;
            if ($locked) {
                $this->select('SELECT RELEASE_LOCK(?)', [$lock]);
            }
        }
    }

    private function suffix(): string
    {
        CorrectionEvidence::require($this->active, 'Read must occur inside a correction snapshot.');

        return $this->writable ? ' FOR UPDATE' : '';
    }

    public function catalog(): array
    {
        // Complete primary-index range scan locks rows and insertion gaps under REPEATABLE READ.
        return $this->select('SELECT '.self::CATALOG.' FROM academic_programs FORCE INDEX (PRIMARY) ORDER BY program_id'.$this->suffix());
    }

    public function registry(int $id): ?array
    {
        return $this->select('SELECT '.implode(', ', CorrectionEvidence::REGISTRY_FIELDS).' FROM sle_fhe_registries WHERE id=?'.$this->suffix(), [$id])[0] ?? null;
    }

    public function collisions(array $item): array
    {
        $found = [];
        // Also check the actual database collation, not just PHP case/space equivalence.
        foreach (['code', 'name', 'short_name'] as $field) {
            if ($item[$field] !== null) {
                foreach ($this->select('SELECT '.self::CATALOG.' FROM academic_programs WHERE code=? OR name=? OR short_name=?'.$this->suffix(), array_fill(0, 3, $item[$field])) as $row) {
                    $found[$row['program_id']] = $row;
                }
            }
        }
        foreach ($this->catalog() as $row) {
            foreach (['code', 'name', 'short_name'] as $field) {
                foreach (['code', 'name', 'short_name'] as $other) {
                    if ($row[$field] !== null && $item[$other] !== null
                        && mb_strtolower(preg_replace('/\s+/u', ' ', trim($row[$field]))) === mb_strtolower(preg_replace('/\s+/u', ' ', trim($item[$other])))) {
                        $found[$row['program_id']] = $row;
                    }
                }
            }
        }

        return array_values($found);
    }

    public function sameText(string $left, string $right): bool
    {
        $this->suffix();
        $collation = $this->catalogCollation; // Verified metadata identifier, never manifest SQL.

        return (int) $this->select("SELECT CONVERT(? USING utf8mb4) COLLATE {$collation} = CONVERT(? USING utf8mb4) COLLATE {$collation} AS equal_text", [$left, $right])[0]['equal_text'] === 1;
    }

    private function write(string $sql, array $bindings): void
    {
        CorrectionEvidence::require($this->active && $this->writable, 'Dry-run store cannot write.');
        if (! $this->writeSchemaChecked) {
            // The executor has now read/locked every affected table. Recheck schema
            // while those metadata locks prevent concurrent trigger/constraint DDL.
            $this->schema(true);
            $this->writeSchemaChecked = true;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        CorrectionEvidence::require($statement->rowCount() === 1, 'Unexpected affected-row count; the complete operation must roll back.');
    }

    public function insertProgram(array $item): array
    {
        $this->write('INSERT INTO academic_programs (code,name,short_name,is_board_program,is_undergraduate,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())',
            [$item['code'], $item['name'], $item['short_name'], (int) $item['is_board_program'], (int) $item['is_undergraduate'], (int) $item['is_active']]);
        $id = (int) $this->pdo->lastInsertId();

        return $this->select('SELECT '.self::CATALOG.' FROM academic_programs WHERE program_id=? FOR UPDATE', [$id])[0];
    }

    public function changeCourse(array $before, string $course, ?string $timestamp = null, bool $restoreTimestamp = false): array
    {
        $current = $this->registry((int) $before['id']);
        CorrectionEvidence::require($current !== null && CorrectionEvidence::rowHash($current) === CorrectionEvidence::rowHash($before), 'Locked registry before-image changed.');
        $sql = 'UPDATE sle_fhe_registries SET course=?, updated_at='.($restoreTimestamp ? '?' : 'NOW()').' WHERE id=? AND BINARY student_id=BINARY ? AND BINARY course=BINARY ? AND updated_at <=> ?';
        $bindings = [$course];
        if ($restoreTimestamp) {
            $bindings[] = $timestamp;
        }
        array_push($bindings, $before['id'], $before['student_id'], $before['course'], $before['updated_at']);
        $this->write($sql, $bindings);

        return $this->registry((int) $before['id']);
    }

    public function record(array $metadata, array $changes): void
    {
        $ids = [];
        foreach ($changes as $change) {
            $details = CorrectionEvidence::json(['manifest_sha256' => $metadata['manifest_sha256'], 'change' => $change]);
            $ids[] = $this->audit('correction.row', $details);
        }
        $this->audit('correction.commit', CorrectionEvidence::json(['metadata' => $metadata, 'row_audit_ids' => $ids]));
    }

    private function audit(string $action, string $details): int
    {
        CorrectionEvidence::require(strlen($details) <= 60000, 'Audit evidence exceeds the existing TEXT column; abort rather than truncate.');
        $this->write('INSERT INTO audit_logs (user_id,action,target_module,details,ip_address,user_agent,role,created_at,updated_at) VALUES (NULL,?,\'database_corrections\',?,NULL,\'SponsorFlow controlled CLI\',\'cli_operator\',NOW(),NOW())', [$action, $details]);

        return (int) $this->pdo->lastInsertId();
    }

    public function receipt(string $manifestHash): ?array
    {
        $rows = $this->select("SELECT details FROM audit_logs WHERE action='correction.commit' AND target_module='database_corrections' AND JSON_UNQUOTE(JSON_EXTRACT(details,'$.metadata.manifest_sha256'))=?".$this->suffix(), [$manifestHash]);
        CorrectionEvidence::require(count($rows) <= 1, 'Conflicting correction receipts found.');
        if ($rows === []) {
            return null;
        }
        $receipt = json_decode($rows[0]['details'], true, 32, JSON_THROW_ON_ERROR);
        $receipt['changes'] = [];
        foreach ($receipt['row_audit_ids'] as $id) {
            $row = $this->select("SELECT details FROM audit_logs WHERE id=? AND action='correction.row' AND target_module='database_corrections'".$this->suffix(), [$id]);
            CorrectionEvidence::require(count($row) === 1, 'Incomplete committed audit evidence.');
            $change = json_decode($row[0]['details'], true, 32, JSON_THROW_ON_ERROR);
            CorrectionEvidence::require($change['manifest_sha256'] === $manifestHash, 'Conflicting audit evidence.');
            $receipt['changes'][] = $change['change'];
        }

        return $receipt;
    }
}
