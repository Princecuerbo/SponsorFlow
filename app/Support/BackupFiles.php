<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use RuntimeException;

class BackupFiles
{
    public function __construct(private FilesystemAdapter $disk, private string $legacyDirectory) {}

    public function write(string $contents): array
    {
        if ($contents === '') {
            throw new RuntimeException('The database snapshot is empty.');
        }
        $name = 'backup-'.date('Y-m-d-H-i-s').'-'.Str::uuid().'.sql';
        $path = 'backups/'.$name;
        if (! $this->disk->put($path, $contents, 'private')) {
            throw new RuntimeException('The database snapshot could not be saved.');
        }
        $saved = $this->disk->get($path);
        if (! is_string($saved) || ! hash_equals(hash('sha256', $contents), hash('sha256', $saved))) {
            throw new RuntimeException('The saved database snapshot failed verification.');
        }

        return ['file_name' => $name, 'file_path' => $path, 'file_size' => strlen($saved)];
    }

    public function resolve(string $path): ?string
    {
        // Accept only database-backup basenames, never arbitrary paths or URLs.
        if (! preg_match('~\Abackups/([A-Za-z0-9][A-Za-z0-9._-]*\.sql)\z~D', $path, $match)) {
            return null;
        }
        foreach ([$this->disk->path('backups'), $this->legacyDirectory] as $directory) {
            $root = realpath($directory);
            $file = realpath($directory.DIRECTORY_SEPARATOR.$match[1]);
            if ($root !== false && $file !== false && dirname($file) === $root && is_file($file) && is_readable($file)) {
                return $file;
            }
        }

        return null;
    }
}
