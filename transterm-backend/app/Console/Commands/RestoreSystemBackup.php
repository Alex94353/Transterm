<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class RestoreSystemBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:backup:restore
        {backup : Backup archive name or absolute path}
        {--database-only : Restore database only}
        {--files-only : Restore files only}
        {--force : Execute restore without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore system from backup archive';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ((bool) $this->option('database-only') && (bool) $this->option('files-only')) {
            $this->error('Use either --database-only or --files-only, not both.');

            return self::FAILURE;
        }

        $archivePath = $this->resolveArchivePath((string) $this->argument('backup'));
        if (! File::exists($archivePath)) {
            $this->error("Backup archive not found: {$archivePath}");

            return self::FAILURE;
        }

        if (! (bool) $this->option('force')) {
            $confirmed = $this->confirm(
                'This operation can overwrite current data. Do you want to continue?',
                false
            );
            if (! $confirmed) {
                $this->warn('Restore cancelled.');

                return self::INVALID;
            }
        }

        $temporaryDirectory = storage_path('app/backups/.restore_' . now()->format('Ymd_His') . '_' . uniqid());
        File::ensureDirectoryExists($temporaryDirectory);

        try {
            $this->extractArchive($archivePath, $temporaryDirectory);

            $databaseOnly = (bool) $this->option('database-only');
            $filesOnly = (bool) $this->option('files-only');

            if (! $filesOnly) {
                $this->restoreDatabase($temporaryDirectory);
            }

            if (! $databaseOnly) {
                $this->restoreFiles($temporaryDirectory);
            }
        } catch (\Throwable $e) {
            $this->error('Restore failed: ' . $e->getMessage());
            File::deleteDirectory($temporaryDirectory);

            return self::FAILURE;
        }

        File::deleteDirectory($temporaryDirectory);
        $this->info('Restore completed successfully.');

        return self::SUCCESS;
    }

    private function resolveArchivePath(string $backupArgument): string
    {
        $isAbsoluteUnix = str_starts_with($backupArgument, DIRECTORY_SEPARATOR);
        $isAbsoluteWindows = (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $backupArgument);
        if ($isAbsoluteUnix || $isAbsoluteWindows) {
            return $backupArgument;
        }

        return rtrim((string) config('backup_strategy.path', storage_path('app/backups')), '/\\')
            . DIRECTORY_SEPARATOR
            . $backupArgument;
    }

    private function extractArchive(string $archivePath, string $temporaryDirectory): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException("Unable to open archive: {$archivePath}");
        }

        $zip->extractTo($temporaryDirectory);
        $zip->close();
    }

    private function restoreDatabase(string $temporaryDirectory): void
    {
        $connectionName = (string) config('database.default');
        $connection = (array) config("database.connections.{$connectionName}");
        $driver = (string) ($connection['driver'] ?? '');

        match ($driver) {
            'sqlite' => $this->restoreSqlite($temporaryDirectory, $connection),
            'mysql', 'mariadb' => $this->restoreMysql($temporaryDirectory, $connection),
            'pgsql' => $this->restorePostgres($temporaryDirectory, $connection),
            default => throw new \RuntimeException("Unsupported database driver for restore: {$driver}"),
        };
    }

    private function restoreSqlite(string $temporaryDirectory, array $connection): void
    {
        $backupSqlitePath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'database.sqlite';
        if (! File::exists($backupSqlitePath)) {
            throw new \RuntimeException('SQLite backup file database.sqlite was not found in archive.');
        }

        $databasePath = (string) ($connection['database'] ?? '');
        if ($databasePath === '' || $databasePath === ':memory:') {
            throw new \RuntimeException('SQLite restore is not supported for empty or in-memory database.');
        }

        $resolvedPath = $this->resolveDatabasePath($databasePath);
        File::ensureDirectoryExists(dirname($resolvedPath));
        File::copy($backupSqlitePath, $resolvedPath);
    }

    private function restoreMysql(string $temporaryDirectory, array $connection): void
    {
        $sqlPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'database.sql';
        if (! File::exists($sqlPath)) {
            throw new \RuntimeException('MySQL backup file database.sql was not found in archive.');
        }

        $process = new Process([
            'mysql',
            '--host=' . (string) ($connection['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($connection['port'] ?? 3306),
            '--user=' . (string) ($connection['username'] ?? ''),
            '--password=' . (string) ($connection['password'] ?? ''),
            (string) ($connection['database'] ?? ''),
        ]);

        $process->setTimeout(300);
        $process->setInput(File::get($sqlPath));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysql restore failed: ' . trim($process->getErrorOutput()));
        }
    }

    private function restorePostgres(string $temporaryDirectory, array $connection): void
    {
        $sqlPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'database.sql';
        if (! File::exists($sqlPath)) {
            throw new \RuntimeException('PostgreSQL backup file database.sql was not found in archive.');
        }

        $process = new Process([
            'psql',
            '--host=' . (string) ($connection['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($connection['port'] ?? 5432),
            '--username=' . (string) ($connection['username'] ?? ''),
            '--dbname=' . (string) ($connection['database'] ?? ''),
        ]);

        $process->setTimeout(300);
        $process->setEnv([
            'PGPASSWORD' => (string) ($connection['password'] ?? ''),
        ]);
        $process->setInput(File::get($sqlPath));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('psql restore failed: ' . trim($process->getErrorOutput()));
        }
    }

    private function restoreFiles(string $temporaryDirectory): void
    {
        $paths = (array) config('backup_strategy.file_paths', ['public', 'private']);
        foreach ($paths as $path) {
            $relativePath = trim((string) $path, '/\\');
            if ($relativePath === '') {
                continue;
            }

            $sourcePath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $relativePath;
            if (! File::isDirectory($sourcePath)) {
                continue;
            }

            $targetPath = storage_path('app/' . $relativePath);
            File::deleteDirectory($targetPath);
            File::ensureDirectoryExists(dirname($targetPath));
            File::copyDirectory($sourcePath, $targetPath);
        }
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
