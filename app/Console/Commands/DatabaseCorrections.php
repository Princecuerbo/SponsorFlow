<?php

namespace App\Console\Commands;

use App\Support\Corrections\CorrectionApproval;
use App\Support\Corrections\CorrectionEvidence;
use App\Support\Corrections\CorrectionExecutor;
use App\Support\Corrections\PdoCorrectionStore;
use Illuminate\Console\Command;
use Illuminate\Database\Connectors\MySqlConnector;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Throwable;

final class DatabaseCorrections extends Command
{
    protected $signature = 'sponsorflow:corrections {manifest : Signed-scope JSON manifest, or non-executable review CSV/XLSX}
        {--approval= : Independently signed institutional approval JSON}
        {--authorization= : Separately signed execution authorization JSON}
        {--target= : Exact database name in the approved target identity}
        {--connection= : Explicit configured mysql/mariadb connection}
        {--apply : Execute the fully authorized atomic operation; default is read-only}';

    protected $description = 'Approval-gated catalog/registry corrections; dry-run by default. No web execution.';

    public function handle(): int
    {
        try {
            $path = (string) $this->argument('manifest');
            if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['csv', 'xlsx'], true)) {
                $this->report(CorrectionEvidence::review($path));

                return self::INVALID;
            }
            $bytes = CorrectionEvidence::file($path);
            $approval = CorrectionEvidence::file((string) $this->option('approval'));
            $authorization = $this->option('authorization') ? CorrectionEvidence::file((string) $this->option('authorization')) : null;
            $operator = trim((new Process(['whoami']))->mustRun()->getOutput());
            CorrectionEvidence::require($operator !== '', 'Cannot establish actual operating-system operator.');
            $gate = new CorrectionApproval($this->laravel['config']->get('corrections', []));
            $executor = new CorrectionExecutor($gate, function (array $context): PdoCorrectionStore {
                $name = (string) $this->option('connection');
                $config = $this->laravel['config']->get('database.connections.'.$name);
                CorrectionEvidence::require(! $this->laravel->configurationIsCached()
                    && is_array($config) && in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)
                    && empty($config['url']) && empty($config['options']) && empty($config['read']) && empty($config['write'])
                    && empty($config['unix_socket']), 'Explicit uncached connection without URL/options/socket/read-write overrides required.');
                foreach (['database', 'host', 'port'] as $field) {
                    CorrectionEvidence::require((string) ($config[$field] ?? '') === $context['manifest']['target'][$field], 'Configured connection differs from approved target.');
                }
                // Pass only reviewed connection settings. Never interpolate configurable
                // isolation/modes/timezone strings or run a PDO init command.
                $safeConfig = array_intersect_key($config, array_flip(['host', 'port', 'database', 'username', 'password']));
                $safeConfig += ['charset' => 'utf8mb4', 'strict' => true, 'use_db_after_connecting' => false,
                    'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false]];
                $pdo = (new MySqlConnector)->connect($safeConfig);

                return new PdoCorrectionStore($pdo, $config, $context['protected']);
            });
            $report = $executor->run($bytes, $approval, $authorization, $operator,
                (string) $this->option('target'), (bool) $this->option('apply'));
            $this->report($report);

            return empty($report['execution_blockers']) ? self::SUCCESS : self::INVALID;
        } catch (Throwable $exception) {
            // SQL/PDO and process diagnostics can contain credentials or institutional data.
            $safe = $exception instanceof \RuntimeException
                && ! $exception instanceof \PDOException && ! $exception instanceof \Symfony\Component\Process\Exception\ProcessFailedException;
            $this->report(['status' => 'STOPPED', 'mode' => $this->option('apply') ? 'APPLY' : 'DRY_RUN',
                'error' => $safe ? $exception->getMessage() : 'Validation or database operation failed; inspect protected diagnostics. Do not retry writes without checking committed audit receipts.']);

            return self::INVALID;
        }
    }

    private function report(array $report): void
    {
        $this->output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
    }
}
