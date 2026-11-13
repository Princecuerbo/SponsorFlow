# Controlled catalog and registry corrections (Phase D1)

This is an opt-in CLI executor, **not an import or an approval authority**. Nothing
in this implementation approves the five C2 proposals or the 86 Phase A rows.
Registry IDs 886 and 1388 and their corresponding Student IDs remain blocked.
The original workbook, review packages, disabled registry seeder, and B1 preflight
are unchanged. There is no correction web route, migration, schema change, or backfill.

## Deployment state and scope

`config/corrections.php` ships with empty approval/execution public-key registries
and `production_apply_enabled = false`. Thus current institutional packages cannot
authorize APPLY. Enrolling trusted public keys and enabling a production maintenance
window require a separately reviewed deployment and explicit user authorization.
Never insert test fixture keys into deployment configuration.

Supported atomic kinds:

| Kind | Permitted data changes |
| --- | --- |
| `catalog` | INSERT selected approved `academic_programs` rows, plus audit evidence |
| `registry` | UPDATE only exact approved registry rows' `course` and `updated_at`, plus audit evidence |
| `rollback_registry` | Restore exact original course/timestamp from committed audit evidence, plus new audit evidence |

Existing catalog IDs and attributes never change. The database assigns new IDs.
Catalog approval does not authorize registry changes. There is no bulk course-text
replacement, automatic verification, student/profile mutation, or sponsorship approval.

The schema used is the existing `academic_programs.program_id`, 14-column registry,
and `audit_logs` with nullable `user_id`, TEXT `details`, `user_agent`, and `role`.
Catalog code has an ordinary index, not a uniqueness constraint. Profile and
sponsorship-program foreign keys remain untouched.

## Interface (documentation only; no live execution authorized)

```
php artisan sponsorflow:corrections <manifest.json> --approval=<approval.json> --target=<exact-db-name> --connection=<configured-connection>
php artisan sponsorflow:corrections <manifest.json> --approval=<approval.json> --authorization=<execution.json> --target=<exact-db-name> --connection=<configured-connection> --apply
```

The first form defaults to DRY_RUN. Including an execution document does not imply
APPLY: `--apply` is still required. Without execution authorization, an otherwise
valid approved manifest can be examined read-only, but its report lists execution
blockers. Missing/invalid institutional approval stops before opening the database.

Passing a Phase A CSV or C2 XLSX always returns `REVIEW_ONLY`, counts its statuses,
reports **zero executable changes**, and exits with status 2, even if a cell says
APPROVED or `--apply` was supplied. Only separately approved canonical JSON is
executable input. No conversion automatically approves a CSV/XLSX.

Reports go to stdout; no evidence file is overwritten or published. Do not redirect
reports to `public`, source evidence, or shared web storage. Dry-run output includes
selected registry IDs/course differences, so treat it as restricted institutional
material. Credentials and Student IDs are not included in the normal CLI report.
The database audit contains complete institutional row images and requires restricted
access and retention.

## Manifest and signature contract

All execution JSON objects use `CorrectionEvidence::json()` canonical encoding:
recursively sort object keys, retain array order, UTF-8 and unescaped slashes,
no pretty-printing. Duplicate keys/noncanonical JSON are rejected. Manifest SHA-256
is over the **exact file bytes**, including any trailing newline. Freeze those bytes
before requesting signatures. No live signing tool or private key is provided.

Common manifest fields:

| Field | Requirement |
| --- | --- |
| `version`, `kind`, `status`, `reference` | `1`, one supported kind, `APPROVED`, unique review reference |
| `target` | Exact string values for `database`, `host`, `port`, `server_hostname`, `server_id`, `account` (`CURRENT_USER()`) |
| `catalog_sha256` | SHA-256 of canonical JSON for the complete current catalog, ordered by program_id |
| `items` | 1–100 explicitly selected approved items, unique references; no skipped PENDING members |

Catalog snapshot includes program_id, code, name, short_name, is_board_program,
is_undergraduate, is_active, created_at, updated_at. Preserve native PDO numeric/NULL
types for catalog hashing. Use the executor's UTC+08:00 session convention when
collecting timestamp evidence. Creating an approved execution subset is a separate
review step; it does not change the original PENDING package.

Every item has `reference`, `approval_status = APPROVED`, and `approval_reference`.

Catalog item fields:

- Exact approved `code` (maximum 50), `name` (maximum 255).
- `short_name` (maximum 255), or NULL with `short_name_null_approved = true`.
- Explicit JSON booleans `is_board_program`, `is_undergraduate`, `is_active`.
- No `program_id` is accepted for insertions.

Registry item fields:

- Integer `registry_id`, exact `student_id`, `original_course`, `replacement_course`.
- Existing `catalog_program_id`; replacement must exactly equal that catalog name.
- `original_row_sha256`, exact `original_updated_at` (or explicit NULL).
- `source_sheet` (`DISTRICT 1` or `DISTRICT 2`) and integer `source_row`.
- Top-level `source.path` and `source.sha256` identify the unchanged original XLSX.

Phase A fingerprints use `registry-row-json-v1`: ordered fields
`id, student_id, sex, civil_status, purok, barangay, municipality, course, year_level,
campus, district, is_verified, created_at, updated_at`, strings for all non-NULL
values, retained NULLs, UTF-8/unescaped slashes. Workbook identity, exact source
coordinates and original course are rechecked. Multiple source rows for the same
normalized Student ID stop execution; the first source occurrence is never chosen.

Each signed document is `{key_id, payload, signature}`. `signature` is base64 RSA
SHA-256 over canonical payload JSON, using an enrolled RSA public key of at least
2048 bits. The key registry binds that key to an independent authority name.

Common signed payload fields: `purpose`, `manifest_sha256`, `authority`, `reference`,
`issued_at`, `expires_at`, and nonempty `evidence` entries (`path`, `sha256`).
Dates require ISO-8601 seconds and explicit timezone; future/expired evidence fails.
Written evidence must exist and match its exact checksum; the manifest itself is
not independent written evidence.

Institutional approval:

- `purpose = institutional_approval`.
- `items` is the ordered list of exactly the selected item references.
- Signed reference equals every item's institutional approval reference.

Separate execution authorization:

- `purpose = execution_authorization`; a different enrolled signing key is required.
- Exact `approval_sha256`, `target`, `operator` (actual OS `whoami`), and
  `operation` (`apply` or `rollback`).
- `exclusive_maintenance = true` is an explicit signed maintenance attestation.
- `backup` and `isolated_test`: `verified = true`, `reference`, `verified_by`
  (different from operator), `verified_at`, and evidence `path`/`sha256`.
- `isolated_test.database` must identify a non-production database.

Signatures prove document integrity and who attested to it. They cannot establish
that an approver is institutionally authorized or a backup was actually restored.
Independent key enrollment, evidence review, restore checks, and isolation review
remain mandatory operational prerequisites. Self-authored approval text or a
synthetic test signature is not institutional approval.

## Connection and transaction safeguards

The CLI requires an explicit uncached Laravel mysql/mariadb connection. URL,
socket, custom PDO options and read/write connection overrides are rejected.
Only reviewed host/port/database/credentials are passed to the connector; SQL
configuration strings are not interpolated from a manifest. Multiple statements
are disabled. Actual database, server, port and account must equal signed identity.
Server `init_connect` must be empty; this must also be established independently
before connecting, because a server login hook executes before client inspection.

Dry-run uses `SET TRANSACTION READ ONLY` and rolls back the snapshot. PDO write
methods additionally reject calls outside a write transaction.

APPLY uses:

1. InnoDB base-table, column, primary-key and catalog-collation checks. It requires
   trigger-inspection privileges and rejects triggers and unexpected incoming
   foreign keys to registry course/timestamp.
2. A database-scoped advisory maintenance lock and REPEATABLE READ.
3. Full primary-index catalog `FOR UPDATE` scan (including insertion gaps), locked
   audit receipt and selected registry rows; no `LOCK TABLES` or implicit-commit DDL.
4. Complete-manifest validation before the first write; signatures, expiry and
   source/evidence hashes are checked again while locks are held.
5. Exact predicates, strict SQL mode, affected-row checks, post-image verification,
   and same-transaction before/after audit writes. Schema is rechecked before writes
   while affected-table metadata locks are held.
6. COMMIT only after all data and audit writes pass. Any exception rolls back all
   selected changes. No retry silently skips a row or refreshes stale evidence.

Collisions compare code/name/short-name across active and inactive entries using
actual database collation plus case/space equivalence. Selected entries are also
compared with each other. Exactly identical approved attributes are no-ops.
Different attributes never overwrite an existing catalog row.

The advisory lock coordinates executor instances; range locks protect against
ordinary concurrent inserts during the transaction. A separately enforced exclusive
maintenance window must also suspend unrelated catalog/registry writers. There is
no new unique index, so this executor cannot prevent an unrelated application from
creating a duplicate **after** the correction transaction has committed.

MariaDB basis: [transaction modes](https://mariadb.com/docs/server/reference/sql-statements/administrative-sql-statements/set-commands/set-transaction),
[InnoDB locks](https://mariadb.com/docs/server/server-usage/storage-engines/innodb/innodb-lock-modes),
[GRANT scope](https://mariadb.com/docs/server/reference/sql-statements/account-management-sql-statements/grant).
Actual MariaDB concurrency/rollback behavior still requires the isolated test below.

## Audit, repeat execution and reversal

`correction.row` audit entries hold complete before/after images. A
`correction.commit` entry holds operator, approval/authorization evidence, manifest
checksum, target identity, catalog snapshots and row audit IDs. `user_id` is NULL
and role is `cli_operator`: no FASSG or Sponsor identity is impersonated.
Evidence that exceeds the existing TEXT capacity aborts rather than truncating.

A repeat of the same manifest validates the committed receipt and current post-images,
then returns ALREADY_APPLIED without inserts, audit duplication or timestamp changes.
A changed catalog/post-image stops the repeat. After an uncertain connection/commit
failure, independently inspect receipts; never assume that retrying is harmless.

Registry reversal uses a **new** `rollback_registry` manifest and new approval and
execution signatures, with `original_manifest_sha256` and the complete original
atomic set of `registry_id`/`student_id` items. It refuses intervening modifications.
Only course and the original updated_at are restored; original correction audit rows
remain, and compensating evidence is appended. A rolled-back forward manifest cannot
be re-executed silently.

Catalog deletion is deliberately **unsupported**. Catalog foreign keys include
cascade-delete associations; an automatic inverse INSERT would risk history.
An unwanted inserted catalog row requires separate institutional review of references
and a separately authorized non-destructive remedy. No schema/association/record
deletion is included in D1.

## Testing and release prerequisites

Database-free tests use PHPUnit directly with `vendor/autoload.php`; they do not
bootstrap Laravel or load `.env`. Synthetic keys/evidence/workbooks exist only in
temporary test directories and are removed afterward.

```
php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php --do-not-cache-result tests/Unit
```

`tests/Integration/DatabaseCorrectionsMariaDbTest.php` is outside the default
phpunit.xml suites and skips without explicit opt-in. It does not provision a
database or credentials. Before running it, separately authorize and verify:

- A MariaDB **10.4** empty schema clone, named `sponsorflow_correction_test_<suffix>`.
  Other versions require privilege/isolation review (including implicit PUBLIC
  privileges in later MariaDB versions).
- A non-root account with only that exact database's grants. Escape database `_`
  characters in grants (`sponsorflow\_correction\_test\_<suffix>`); unescaped database
  wildcards, global grants, roles, other databases and GRANT OPTION are rejected.
  The account needs SELECT/INSERT/UPDATE and trigger-metadata visibility.
- An independent isolation-review document and checksum. Do not use copied real
  institutional rows. Existing schema is required; no migrations or DDL are run.
- An external private JSON config with `host` (localhost/127.0.0.1), `port`,
  `database`, `username`, `password`, and `isolation_review` containing `reference`,
  `reviewed_by`, `empty_schema_only = true`, `path`, `sha256`.
- Process-local `SPONSORFLOW_D1_TEST_CONFIG` pointing to that config and
  `SPONSORFLOW_D1_ISOLATED_TEST = AUTHORIZED_EMPTY_TEST_DATABASE`.

The integration test uses raw PDO with explicit config and no Laravel bootstrap.
Laravel cached configuration, `.env`, storage, queues, sessions, mail, notifications
and application events cannot supply alternative connections or side effects.
Identity/grants/schema and empty-table checks run before synthetic fixture inserts.
It exercises real transactions, idempotency, exact-row preservation, reversal and a
second-connection insertion lock timeout. It leaves synthetic data/audits for review
and refuses a later run on the nonempty schema. No automatic database cleanup runs.

This is an executor integration test, not full HTTP/browser acceptance. Existing
populated profile/verification/sponsorship preservation is checked using database-free
substitutes; full application integration with synthetic related records remains a
separate isolated acceptance step. Before production authorization, also test deadlocks,
process interruption, audit-write failure, stale external evidence, actual grants,
and backup restoration in the approved isolated environment.

Do not run the integration file, APPLY, imports, migrations, or production corrections
as part of Phase D1 implementation. The original PENDING packages stay unchanged.
