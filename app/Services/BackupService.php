<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Phase 7: backup creation, pruning, restore and download.
 *
 * A backup is a single zip stored in storage/app/backups containing:
 *   - database.sql   (mysqldump of the application database)
 *   - files/...      (project files, minus vendor, node_modules, .git and
 *                     the backups directory itself)
 *
 * Works on shared hosting: no root, no queue daemon — everything is
 * synchronous PHP + the mysqldump/mysql client binaries (bundled with the
 * MySQL client package on virtually every host; the updater refuses to
 * run a backup when mysqldump is missing).
 */
class BackupService
{
    public const DIR = 'backups';

    /** Relative paths (from project root) never included in a backup. */
    public const EXCLUDES = [
        'vendor',
        'node_modules',
        '.git',
        'storage/app/backups',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
    ];

    public function __construct(protected SettingsService $settings) {}

    protected function root(?string $root): string
    {
        return rtrim($root ?? base_path(), DIRECTORY_SEPARATOR);
    }

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('local');
    }

    /**
     * Create a backup. Throws \RuntimeException on failure.
     */
    public function create(string $type = 'manual', ?string $root = null): Backup
    {
        $root = $this->root($root);
        $this->ensureDir();

        $stamp = now('UTC')->format('Ymd-His');
        $filename = "backup-{$stamp}-{$type}.zip";
        $tmpZip = sys_get_temp_dir().'/meb-backup-'.uniqid().'.zip';

        try {
            $sqlPath = $this->dumpDatabase($root);

            $zip = new ZipArchive();
            if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create the backup archive.');
            }

            $zip->addFile($sqlPath, 'database.sql');
            $this->addFiles($zip, $root, 'files');
            $zip->close();
            @unlink($sqlPath);

            $this->disk()->putFileAs(self::DIR, new \Illuminate\Http\File($tmpZip), $filename);
            @unlink($tmpZip);

            $path = Storage::disk('local')->path(self::DIR.'/'.$filename);

            $backup = Backup::create([
                'filename' => $filename,
                'type' => $type,
                'size_bytes' => filesize($path) ?: 0,
                'version' => (string) ($this->settings->get('updates.installed_version') ?: config('version.version', '0.0.1')),
                'commit_sha' => $this->settings->get('updates.installed_sha'),
                'notes' => null,
                'created_at' => now('UTC'),
            ]);

            $this->prune();

            return $backup;
        } catch (\Throwable $e) {
            @unlink($tmpZip);

            throw $e;
        }
    }

    /**
     * Restore a backup over the project. Throws on failure.
     * The .env files and the backups directory are never overwritten.
     */
    public function restore(Backup $backup, ?string $root = null): void
    {
        $root = $this->root($root);
        $zipPath = Storage::disk('local')->path(self::DIR.'/'.$backup->filename);

        if (! is_file($zipPath)) {
            throw new \RuntimeException('Backup file is missing from storage.');
        }

        $tmpDir = sys_get_temp_dir().'/meb-restore-'.uniqid();
        mkdir($tmpDir, 0755, true);

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('Backup archive is corrupt.');
            }
            $zip->extractTo($tmpDir);
            $zip->close();

            $filesDir = $tmpDir.'/files';
            if (! is_dir($filesDir)) {
                throw new \RuntimeException('Backup archive has no files payload.');
            }

            // Restore code files first, then the database.
            // Files added after the backup was taken (e.g. by a failed
            // update) are removed so the rollback is complete — except
            // preserved paths (.env, private storage, logs, backups).
            $preserve = ['.env', '.env.example', '.env.backup', 'storage/app', 'storage/logs'];
            $this->copyTree($filesDir, $root, ['.env', '.env.example', '.env.backup', 'storage/app/backups']);
            $this->removeAddedFiles($filesDir, $root, $preserve);

            $sqlPath = $tmpDir.'/database.sql';
            if (is_file($sqlPath)) {
                $this->importDatabase($sqlPath);
            }

            $this->clearCaches();
        } finally {
            $this->removeDir($tmpDir);
        }
    }

    public function downloadPath(Backup $backup): string
    {
        $path = Storage::disk('local')->path(self::DIR.'/'.$backup->filename);

        if (! is_file($path)) {
            throw new \RuntimeException('Backup file is missing from storage.');
        }

        return $path;
    }

    public function delete(Backup $backup): void
    {
        $this->disk()->delete(self::DIR.'/'.$backup->filename);
        $backup->delete();
    }

    /**
     * Keep only the newest N backups (updates.backup_retention, default 7).
     * Pre-update backups are never pruned automatically — only manual/auto.
     */
    public function prune(): void
    {
        $keep = (int) $this->settings->get('updates.backup_retention', 7);
        $keep = max(1, $keep);

        $old = Backup::whereIn('type', ['manual', 'auto'])
            ->orderByDesc('id')
            ->skip($keep)
            ->take(100)
            ->get();

        foreach ($old as $backup) {
            $this->delete($backup);
        }
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function ensureDir(): void
    {
        if (! $this->disk()->exists(self::DIR)) {
            $this->disk()->makeDirectory(self::DIR);
        }
    }

    /**
     * Environment for subprocesses: string values only (proc_open rejects
     * arrays such as $_SERVER['argv'] under the test runner).
     */
    protected function processEnv(array $extra = []): array
    {
        $env = [];
        foreach (array_merge($_SERVER, $_ENV) as $key => $value) {
            if (is_string($key) && (is_string($value) || is_int($value) || is_float($value) || is_bool($value))) {
                $env[$key] = (string) $value;
            }
        }

        return array_merge($env, $extra);
    }

    /**
     * Dump the database with mysqldump. Returns the temp .sql path.
     */
    protected function dumpDatabase(string $root): string
    {
        $binary = trim((string) shell_exec('command -v mysqldump 2>/dev/null'));
        if ($binary === '' || ! is_executable($binary)) {
            throw new \RuntimeException('mysqldump is not available on this server — cannot back up the database.');
        }

        $cfg = config('database.connections.mysql');
        $tmp = sys_get_temp_dir().'/meb-db-'.uniqid().'.sql';

        // Password via environment (MYSQL_PWD) so it never appears in argv.
        $env = $this->processEnv([
            'MYSQL_PWD' => (string) ($cfg['password'] ?? ''),
        ]);

        $cmd = escapeshellarg($binary)
            .' --host='.escapeshellarg((string) ($cfg['host'] ?? '127.0.0.1'))
            .' --port='.escapeshellarg((string) ($cfg['port'] ?? '3306'))
            .' --user='.escapeshellarg((string) ($cfg['username'] ?? ''))
            .' --single-transaction --routines --triggers --no-tablespaces'
            .' '.escapeshellarg((string) ($cfg['database'] ?? ''));

        $descriptor = [1 => ['file', $tmp, 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptor, $pipes, null, $env);

        if (! is_resource($process)) {
            throw new \RuntimeException('Could not start mysqldump.');
        }

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($exit !== 0 || ! is_file($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            throw new \RuntimeException('Database dump failed.'.($stderr ? ' mysqldump: '.trim($stderr) : ''));
        }

        return $tmp;
    }

    protected function importDatabase(string $sqlPath): void
    {
        $binary = trim((string) shell_exec('command -v mysql 2>/dev/null'));
        if ($binary === '' || ! is_executable($binary)) {
            throw new \RuntimeException('The mysql client is not available — cannot restore the database.');
        }

        $cfg = config('database.connections.mysql');
        $env = $this->processEnv([
            'MYSQL_PWD' => (string) ($cfg['password'] ?? ''),
        ]);

        $cmd = escapeshellarg($binary)
            .' --host='.escapeshellarg((string) ($cfg['host'] ?? '127.0.0.1'))
            .' --port='.escapeshellarg((string) ($cfg['port'] ?? '3306'))
            .' --user='.escapeshellarg((string) ($cfg['username'] ?? ''))
            .' '.escapeshellarg((string) ($cfg['database'] ?? ''))
            .' < '.escapeshellarg($sqlPath);

        $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptor, $pipes, null, $env);

        if (! is_resource($process)) {
            throw new \RuntimeException('Could not start the mysql client.');
        }

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        if ($exit !== 0) {
            throw new \RuntimeException('Database restore failed.'.($stderr ? ' mysql: '.trim($stderr) : ''));
        }
    }

    protected function addFiles(ZipArchive $zip, string $root, string $prefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relative = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            if ($this->isExcluded($relative)) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($prefix.'/'.$relative);
            } else {
                $zip->addFile($file->getPathname(), $prefix.'/'.$relative);
            }
        }
    }

    protected function isExcluded(string $relative): bool
    {
        foreach (self::EXCLUDES as $exclude) {
            if ($relative === $exclude || str_starts_with($relative, $exclude.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively copy $from over $to, skipping $skipPaths (relative).
     */
    public function copyTree(string $from, string $to, array $skipPaths = []): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relative = ltrim(str_replace($from, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            foreach ($skipPaths as $skip) {
                if ($relative === $skip || str_starts_with($relative, rtrim($skip, '/').'/')) {
                    continue 2;
                }
            }

            $target = $to.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($file->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $dir = dirname($target);
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                copy($file->getPathname(), $target);
            }
        }
    }

    /**
     * Delete files/dirs under $root that are not present in $filesDir
     * (the backup payload), so a restore/rollback is complete. Paths in
     * $preserve are never touched.
     */
    protected function removeAddedFiles(string $filesDir, string $root, array $preserve = []): void
    {
        $known = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filesDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            $rel = substr($file->getPathname(), strlen($filesDir) + 1);
            $known[str_replace(DIRECTORY_SEPARATOR, '/', $rel)] = true;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $rel = substr($file->getPathname(), strlen($root) + 1);
            $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);

            $skip = false;
            foreach ($preserve as $p) {
                $p = rtrim($p, '/');
                if ($rel === $p || str_starts_with($rel, $p.'/')) {
                    $skip = true;
                    break;
                }
            }
            if ($skip || isset($known[$rel])) {
                continue;
            }

            if ($file->isDir()) {
                @rmdir($file->getPathname()); // only removes it if now empty
            } else {
                @unlink($file->getPathname());
            }
        }
    }

    protected function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }

    protected function clearCaches(): void
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Throwable $e) {
            // Cache clearing is best-effort after a restore.
        }
    }
}
