<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DatabaseBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            $filename = 'backup-'.now()->format('Y-m-d-H-i-s').'.sql';
            $backupDir = storage_path('app/backups');

            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $absolutePath = $backupDir.DIRECTORY_SEPARATOR.$filename;
            file_put_contents($absolutePath, $dumpContent);

            $relativePath = 'backups/'.$filename;
            $backup = DatabaseBackup::query()->create([
                'file_name' => $filename,
                'file_path' => $relativePath,
                'file_size' => filesize($absolutePath),
                'created_by_user_id' => $request->user()->id,
                'status' => 'completed',
                'created_at' => now(),
            ]);

            AuditLog::record('admin.backup.created', 'database_backups', $request->user(), $request->ip(), $backup->file_name);

            return back()->with('success', 'Database backup snapshot successfully created: '.$filename);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Backup failed: '.$exception->getMessage());
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
        abort_if($backup->status !== 'completed' || ! Storage::disk('local')->exists($backup->file_path), 404);
        AuditLog::record('admin.backup.downloaded', 'database_backups', $request->user(), $request->ip(), $backup->file_name);

        return response()->download(Storage::disk('local')->path($backup->file_path), $backup->file_name);
    }

    public function download(Request $request, DatabaseBackup $backup): BinaryFileResponse
    {
        return $this->downloadBackup($request, $backup);
    }

    public function restore(Request $request, DatabaseBackup $backup): RedirectResponse
    {
        abort_unless($backup->status === 'completed' && Storage::disk('local')->exists($backup->file_path), 404);

        try {
            $sql = Storage::disk('local')->get($backup->file_path);
            $header = trim(substr($sql, 0, 256));

            if ($header === '' || ! preg_match('/(CREATE TABLE|INSERT INTO|DROP TABLE|SET FOREIGN_KEY_CHECKS)/i', $header)) {
                throw new \RuntimeException('Backup snapshot header is invalid or not a recognized SQL dump.');
            }

            $driver = DB::getDriverName();
            if (! in_array($driver, ['mysql', 'sqlite'], true)) {
                throw new \RuntimeException('Database restore is supported only for MySQL or SQLite drivers.');
            }

            DB::transaction(function () use ($sql, $driver): void {
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                }

                if ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF');
                }

                try {
                    DB::unprepared($sql);
                } finally {
                    if ($driver === 'mysql') {
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    }

                    if ($driver === 'sqlite') {
                        DB::statement('PRAGMA foreign_keys = ON');
                    }
                }
            });

            AuditLog::record('admin.backup.restored', 'database_backups', $request->user(), $request->ip(), $backup->file_name, $request->userAgent(), $request->user()?->role?->value);

            return back()->with('success', "Database restored from {$backup->file_name}.");
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Restore failed: '.$exception->getMessage());
        }
    }

    private function buildDatabaseDump(): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            return $this->buildMysqlDump();
        }

        if ($driver === 'sqlite') {
            return $this->buildSqliteDump();
        }

        throw new \RuntimeException('Database backup is supported only for MySQL or SQLite drivers.');
    }

    private function buildMysqlDump(): string
    {
        $dbName = config('database.connections.mysql.database');
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_'.$dbName;

        $dumpContent = "-- SponsorFlow System Backup\n";
        $dumpContent .= "-- Date: ".now()->toDateTimeString()."\n\n";
        $dumpContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0]->{'Create Table'};

            $dumpContent .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $dumpContent .= $createTable.";\n\n";

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($value): string {
                    return is_null($value) ? 'NULL' : DB::getPdo()->quote((string) $value);
                }, (array) $row);

                if (! empty($values)) {
                    $dumpContent .= "INSERT INTO `{$tableName}` VALUES (".implode(', ', $values).");\n";
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
        $dumpContent .= "-- Date: ".now()->toDateTimeString()."\n\n";

        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

        foreach ($tables as $table) {
            $tableName = $table->name;
            $tableSql = DB::table('sqlite_master')
                ->where('type', 'table')
                ->where('name', $tableName)
                ->value('sql');

            $dumpContent .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            if (! empty($tableSql)) {
                $dumpContent .= $tableSql.";\n\n";
            }

            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($value): string {
                    return is_null($value) ? 'NULL' : DB::getPdo()->quote((string) $value);
                }, (array) $row);

                if (! empty($values)) {
                    $dumpContent .= "INSERT INTO `{$tableName}` VALUES (".implode(', ', $values).");\n";
                }
            }

            $dumpContent .= "\n";
        }

        return $dumpContent;
    }
}
