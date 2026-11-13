<?php

namespace App\Console\Commands;

use App\Support\SleFhe\RegistryImportPreflight;
use App\Support\SleFhe\RegistryPreflightSnapshot;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class RegistryPreflight extends Command
{
    protected $signature = 'sponsorflow:registry-preflight {--source= : Explicit path to the institutional XLSX workbook}';

    protected $description = 'Report registry source/catalog conflicts as read-only JSON. Never imports or approves records.';

    public function handle(RegistryImportPreflight $preflight, RegistryPreflightSnapshot $snapshot): int
    {
        $stage = 'source';
        try {
            if ($this->option('source') !== null && trim((string) $this->option('source')) === '') {
                throw new RuntimeException('An explicit --source must name a workbook; no fallback performed.');
            }
            $override = $this->option('source') ?? $this->laravel['config']->get('sle-fhe.registry_source');
            $paths = is_string($override) && trim($override) !== '' ? [trim($override)] : [
                $this->laravel->storagePath('app/SLE-FHE DATA.xlsx'),
                $this->laravel->basePath('SLE-FHE DATA.xlsx'),
            ];
            $path = current(array_filter($paths, 'is_file')) ?: $paths[0];
            // Fully validate source structure before obtaining a database connection.
            $workbook = $preflight->readWorkbook($path);
            $stage = 'snapshot';
            $data = $snapshot->read($this->laravel['db']->connection()->getPdo());
            $stage = 'comparison';
            $report = $preflight->inspect($workbook, $data['catalog'], $data['registry']);
            $report['database'] = $data['database'];
            $report['fingerprint_format'] = 'B1 SHA-256 of recursively key-sorted JSON, UTF-8, unescaped Unicode/slashes; distinct from Phase A.';
            $this->report($report);

            return $report['has_issues'] ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            // PDO diagnostics can expose connection details; do not print them.
            $this->report(['mode' => 'READ_ONLY', 'writes_enabled' => false, 'import_authorized' => false,
                'status' => 'STOPPED', 'stage' => $stage,
                'source' => $workbook['source'] ?? ['requested_path' => $path ?? null],
                'error' => $stage === 'snapshot' ? 'Read-only database snapshot failed. No complete comparison was performed.' : $exception->getMessage()]);

            return self::INVALID;
        }
    }

    private function report(array $report): void
    {
        $this->output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
    }
}
