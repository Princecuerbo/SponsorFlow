<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DatabaseBackup;
use App\Support\BackupFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(Request $request): View
    {
        return $this->listBackups($request);
    }

    public function listBackups(Request $request): View
    {
        return view('admin.backups.index', [
            'backups' => DatabaseBackup::query()->with('createdBy')->latest('created_at')->paginate(20),
        ]);
    }

    public function createBackup(Request $request): RedirectResponse
    {
        try {
            $dumpContent = $this->buildDatabaseDump();
            $file = $this->files()->write($dumpContent);
            DB::transaction(function () use ($request, $file): void {
                $backup = DatabaseBackup::query()->create($file + [
                    'created_by_user_id' => $request->user()->id,
                    'status' => 'completed',
                    'created_at' => now(),
                ]);
                AuditLog::record('admin.backup.created', 'database_backups', $request->user(), $request->ip(), $backup->file_name);
            });

            return back()->with('success', 'Database table snapshot created and file contents verified: '.$file['file_name']);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'The backup could not be completed. Check the server log and backup storage before retrying.');
        }
    }

    public function create(Request $request): RedirectResponse
    {
        return $this->createBackup($request);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->createBackup($request);
    }

    public function run(Request $request): RedirectResponse
    {
        return $this->createBackup($request);
    }

    public function downloadBackup(Request $request, DatabaseBackup $backup): BinaryFileResponse
    {
        $path = $this->files()->resolve($backup->file_path);
        abort_if($backup->status !== 'completed' || $path === null, 404);
        AuditLog::record('admin.backup.downloaded', 'database_backups', $request->user(), $request->ip(), $backup->file_name);

        return response()->download($path, basename($path));
    }

    public function download(Request $request, DatabaseBackup $backup): BinaryFileResponse
    {
        return $this->downloadBackup($request, $backup);
    }

    public function restore(Request $request, DatabaseBackup $backup): RedirectResponse
    {
        // SQL DDL implicitly commits in MariaDB; an HTTP transaction cannot safely roll it back.
        // Keep this route fail-closed for old links and crafted requests.
        throw ValidationException::withMessages([
            'backup' => 'Web restore is disabled. Restore requires a separately verified maintenance procedure and recovery backup.',
        ]);
    }

    private function files(): BackupFiles
    {
        return new BackupFiles(Storage::disk('local'), storage_path('app/backups'));
    }

    private function buildDatabaseDump(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new \RuntimeException('Database backup is supported only for MySQL or SQLite drivers.');
        }
        if ($driver === 'mysql') {
            if ($connection->transactionLevel() !== 0) {
                throw new \RuntimeException('A database snapshot requires its own read-only transaction.');
            }
            $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $connection->statement('SET TRANSACTION READ ONLY');
        }
        $connection->beginTransaction();
        try {
            return $driver === 'mysql' ? $this->buildMysqlDump() : $this->buildSqliteDump();
        } finally {
            $connection->rollBack();
        }
    }

    private function buildMysqlDump(): string
    {
        $tables = DB::select('SELECT TABLE_NAME AS name, TABLE_TYPE AS kind, ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
        foreach ($tables as $table) {
            if ($table->kind !== 'BASE TABLE' || $table->engine !== 'InnoDB') {
                throw new \RuntimeException('Use the controlled backup procedure for views or non-InnoDB tables.');
            }
        }

        $dumpContent = "-- SponsorFlow System Backup\n";
        $dumpContent .= '-- Date: '.now()->toDateTimeString()."\n\n";
        $dumpContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->name;
            $identifier = str_replace('`', '``', $tableName);
            $createTable = DB::select("SHOW CREATE TABLE `{$identifier}`")[0]->{'Create Table'};

            $dumpContent .= "DROP TABLE IF EXISTS `{$identifier}`;\n";
            $dumpContent .= $createTable.";\n\n";

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($value): string {
                    return is_null($value) ? 'NULL' : DB::getPdo()->quote((string) $value);
                }, (array) $row);

                if (! empty($values)) {
                    $columns = implode(', ', array_map(fn ($column) => '`'.str_replace('`', '``', $column).'`', array_keys((array) $row)));
                    $dumpContent .= "INSERT INTO `{$identifier}` ({$columns}) VALUES (".implode(', ', $values).");\n";
                }
            }

            $dumpContent .= "\n\n";
        }

        $dumpContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $dumpContent;
    }

    private function buildSqliteDump(): string
    {
        $dumpContent = "-- SponsorFlow System Backup\n";
        $dumpContent .= '-- Date: '.now()->toDateTimeString()."\n\n";

        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

        foreach ($tables as $table) {
            $tableName = $table->name;
            $identifier = str_replace('`', '``', $tableName);
            $tableSql = DB::table('sqlite_master')
                ->where('type', 'table')
                ->where('name', $tableName)
                ->value('sql');

            $dumpContent .= "DROP TABLE IF EXISTS `{$identifier}`;\n";
            if (! empty($tableSql)) {
                $dumpContent .= $tableSql.";\n\n";
            }

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($value): string {
                    return is_null($value) ? 'NULL' : DB::getPdo()->quote((string) $value);
                }, (array) $row);

                if (! empty($values)) {
                    $columns = implode(', ', array_map(fn ($column) => '`'.str_replace('`', '``', $column).'`', array_keys((array) $row)));
                    $dumpContent .= "INSERT INTO `{$identifier}` ({$columns}) VALUES (".implode(', ', $values).");\n";
                }
            }

            $dumpContent .= "\n";
        }

        return $dumpContent;
    }
}
