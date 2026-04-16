<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class CreateSystemBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:backup:create
        {--label=manual : Backup label used in file name}
        {--skip-files : Skip storage file backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database and storage backup archive';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $backupRoot = (string) config('backup_strategy.path', storage_path('app/backups'));
        File::ensureDirectoryExists($backupRoot);

        $timestamp = now()->format('Ymd_His');
        $label = $this->sanitizeLabel((string) $this->option('label'));
        $backupBaseName = "backup_{$timestamp}_{$label}";
        $temporaryDirectory = $backupRoot . DIRECTORY_SEPARATOR . $backupBaseName . '_tmp';
        $archivePath = $backupRoot . DIRECTORY_SEPARATOR . $backupBaseName . '.zip';

        File::deleteDirectory($temporaryDirectory);
        File::ensureDirectoryExists($temporaryDirectory);

        try {
            $databaseFileName = $this->dumpDatabase($temporaryDirectory);
            $filesIncluded = ! (bool) $this->option('skip-files');

            if ($filesIncluded) {
                $this->copyStorageDirectories($temporaryDirectory);
            }

            $this->writeMetadata($temporaryDirectory, $databaseFileName, $filesIncluded, $label);
            $this->createArchive($temporaryDirectory, $archivePath);
            $this->pruneOldBackups($backupRoot);

            $this->info("Backup created: {$archivePath}");
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());

            File::deleteDirectory($temporaryDirectory);

            return self::FAILURE;
        }

        File::deleteDirectory($temporaryDirectory);

        return self::SUCCESS;
    }

    private function sanitizeLabel(string $label): string
    {
        $resolved = Str::slug(trim($label));

        return $resolved !== '' ? $resolved : 'manual';
    }

    private function dumpDatabase(string $temporaryDirectory): string
    {
        $connectionName = config('database.default');
        $connection = (array) config("database.connections.{$connectionName}");
        $driver = (string) ($connection['driver'] ?? '');

        return match ($driver) {
            'sqlite' => $this->dumpSqlite($temporaryDirectory, $connection),
            'mysql', 'mariadb' => $this->dumpMysql($temporaryDirectory, $connection),
            'pgsql' => $this->dumpPostgres($temporaryDirectory, $connection),
            default => throw new \RuntimeException("Unsupported database driver for backup: {$driver}"),
        };
    }

    private function dumpSqlite(string $temporaryDirectory, array $connection): string
    {
        $databasePath = (string) ($connection['database'] ?? '');
        if ($databasePath === '' || $databasePath === ':memory:') {
            throw new \RuntimeException('SQLite backup is not supported for empty or in-memory database.');
        }

        $resolvedPath = $this->resolveDatabasePath($databasePath);
        if (! File::exists($resolvedPath)) {
            throw new \RuntimeException("SQLite database file not found: {$resolvedPath}");
        }

        $target = $temporaryDirectory . DIRECTORY_SEPARATOR . 'database.sqlite';
        File::copy($resolvedPath, $target);

        return 'database.sqlite';
    }

    private function dumpMysql(string $temporaryDirectory, array $connection): string
    {
        $outputPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'database.sql';
        $process = new Process([
            'mysqldump',
            '--host=' . (string) ($connection['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($connection['port'] ?? 3306),
            '--user=' . (string) ($connection['username'] ?? ''),
            '--password=' . (string) ($connection['password'] ?? ''),
            '--single-transaction',
            '--skip-lock-tables',
            '--skip-comments',
            (string) ($connection['database'] ?? ''),
        ]);

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump failed: ' . trim($process->getErrorOutput()));
        }

        File::put($outputPath, $process->getOutput());

        return 'database.sql';
    }

    private function dumpPostgres(string $temporaryDirectory, array $connection): string
    {
        $outputPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'database.sql';
        $process = new Process([
            'pg_dump',
            '--host=' . (string) ($connection['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($connection['port'] ?? 5432),
            '--username=' . (string) ($connection['username'] ?? ''),
            '--no-owner',
            '--no-privileges',
            '--format=plain',
            (string) ($connection['database'] ?? ''),
        ]);

        $process->setTimeout(300);
        $process->setEnv([
            'PGPASSWORD' => (string) ($connection['password'] ?? ''),
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('pg_dump failed: ' . trim($process->getErrorOutput()));
        }

        File::put($outputPath, $process->getOutput());

        return 'database.sql';
    }

    private function copyStorageDirectories(string $temporaryDirectory): void
    {
        $paths = (array) config('backup_strategy.file_paths', ['public', 'private']);
        foreach ($paths as $path) {
            $relativePath = trim((string) $path, '/\\');
            if ($relativePath === '') {
                continue;
            }

            $sourcePath = storage_path('app/' . $relativePath);
            if (! File::isDirectory($sourcePath)) {
                continue;
            }

            $targetPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $relativePath;
            File::ensureDirectoryExists(dirname($targetPath));
            File::copyDirectory($sourcePath, $targetPath);
        }
    }

    private function writeMetadata(
        string $temporaryDirectory,
        string $databaseFileName,
        bool $filesIncluded,
        string $label
    ): void {
        $connectionName = (string) config('database.default');
        $connection = (array) config("database.connections.{$connectionName}");

        $metadata = [
            'created_at' => now()->toIso8601String(),
            'app_env' => (string) config('app.env'),
            'label' => $label,
            'files_included' => $filesIncluded,
            'database' => [
                'connection' => $connectionName,
                'driver' => (string) ($connection['driver'] ?? ''),
                'database_file' => $databaseFileName,
            ],
        ];

        File::put(
            $temporaryDirectory . DIRECTORY_SEPARATOR . 'metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function createArchive(string $sourceDirectory, string $archivePath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create archive at {$archivePath}");
        }

        $sourceLength = strlen($sourceDirectory) + 1;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDirectory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $relativePath = substr($path, $sourceLength);

            if ($item->isDir()) {
                $zip->addEmptyDir(str_replace('\\', '/', $relativePath));
                continue;
            }

            $zip->addFile($path, str_replace('\\', '/', $relativePath));
        }

        $zip->close();
    }

    private function pruneOldBackups(string $backupRoot): void
    {
        $keepLast = max(1, (int) config('backup_strategy.keep_last', 30));
        $files = collect(File::files($backupRoot))
            ->filter(fn (\SplFileInfo $file) => str_ends_with($file->getFilename(), '.zip'))
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        if ($files->count() <= $keepLast) {
            return;
        }

        $files->slice($keepLast)->each(function (\SplFileInfo $file): void {
            File::delete($file->getPathname());
        });
    }

    private function resolveDatabasePath(string $databasePath): string
    {
        $isAbsoluteUnix = str_starts_with($databasePath, DIRECTORY_SEPARATOR);
        $isAbsoluteWindows = (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $databasePath);

        if ($isAbsoluteUnix || $isAbsoluteWindows) {
            return $databasePath;
        }

        return database_path($databasePath);
    }
}
