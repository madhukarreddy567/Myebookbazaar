<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Services\BackupService;
use App\Services\GitHubService;
use App\Services\SettingsService;
use App\Services\UpdateException;
use App\Services\UpdateService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 7: tests that exercise the REAL database import path
 * (BackupService::restore / UpdateService rollback).
 *
 * These deliberately do NOT use DatabaseTransactions: the mysql import runs
 * in a separate subprocess and would deadlock on the test transaction's
 * table metadata locks. Everything created here is cleaned up manually in
 * tearDown().
 */
class Phase7RestoreIntegrationTest extends TestCase
{
    protected SettingsService $settings;

    protected array $tmpDirs = [];
    protected array $tmpFiles = [];
    protected array $backupIds = [];
    protected array $adminIds = [];
    protected array $originalSettings = [];
    protected int $maxAuditId = 0;

    protected const SETTING_KEYS = [
        'updates.enabled', 'updates.repo_owner', 'updates.repo_name',
        'updates.branch', 'updates.channel', 'updates.backup_retention',
        'updates.github_token', 'updates.installed_sha', 'updates.installed_version',
        'updates.latest_sha', 'updates.last_checked_at', 'updates.release_tag',
        'updates.release_name', 'updates.release_notes', 'security.maintenance_enabled',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->settings = app(SettingsService::class);

        $this->maxAuditId = (int) AuditLog::max('id');

        foreach (self::SETTING_KEYS as $key) {
            $this->originalSettings[$key] = $this->settings->get($key);
        }

        $this->settings->set('updates.enabled', '1');
        $this->settings->set('updates.repo_owner', 'acme');
        $this->settings->set('updates.repo_name', 'myebookbazaar');
        $this->settings->set('updates.branch', 'main');
        $this->settings->set('updates.channel', 'stable');
        $this->settings->set('updates.github_token', 'ghp_testtoken123');
    }

    protected function tearDown(): void
    {
        foreach (self::SETTING_KEYS as $key) {
            $this->settings->set($key, $this->originalSettings[$key] ?? null);
        }

        $filenames = Backup::whereIn('id', $this->backupIds)->pluck('filename')->all();
        foreach ($filenames as $filename) {
            Storage::disk('local')->delete(BackupService::DIR.'/'.$filename);
        }
        Backup::whereIn('id', $this->backupIds)->delete();
        Admin::whereIn('id', $this->adminIds)->delete();
        AuditLog::where('id', '>', $this->maxAuditId)->delete();

        foreach ($this->tmpDirs as $dir) {
            $this->removeDir($dir);
        }
        foreach ($this->tmpFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    protected function makeTempDir(): string
    {
        // Under the project, not the system temp dir (see UpdaterPhase7Test).
        $base = storage_path('framework/testing/meb-restore-test');
        if (! is_dir($base)) {
            mkdir($base, 0755, true);
        }
        $dir = $base.'/'.uniqid();
        mkdir($dir, 0755, true);
        $this->tmpDirs[] = $dir;

        return $dir;
    }

    protected function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }

    protected function makeTarball(string $sha, array $files): string
    {
        $short = substr($sha, 0, 7);
        $work = $this->makeTempDir();
        $top = $work.'/myebookbazaar-'.$short;
        mkdir($top, 0755, true);
        foreach ($files as $name => $content) {
            $path = $top.'/'.$name;
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($path, $content);
        }

        $out = sys_get_temp_dir().'/meb-tar-'.uniqid().'.tar.gz';
        $cmd = 'tar -czf '.escapeshellarg($out).' -C '.escapeshellarg($work).' '.escapeshellarg('myebookbazaar-'.$short);
        exec($cmd, $o, $exit);
        $this->assertSame(0, $exit, 'tar failed');
        $this->tmpFiles[] = $out;

        return $out;
    }

    protected function updateService(string $root): UpdateService
    {
        return new UpdateService(
            app(SettingsService::class),
            app(GitHubService::class),
            app(BackupService::class),
            $root,
        );
    }

    public function test_backup_restore_puts_files_back(): void
    {
        $root = $this->makeTempDir();
        file_put_contents($root.'/data.txt', 'original content');

        $service = app(BackupService::class);
        $backup = $service->create('manual', $root);
        $this->backupIds[] = $backup->id;

        // Tamper, then restore.
        file_put_contents($root.'/data.txt', 'tampered');
        $service->restore($backup, $root);

        $this->assertSame('original content', file_get_contents($root.'/data.txt'));
    }

    public function test_restore_removes_files_added_after_backup(): void
    {
        $root = $this->makeTempDir();
        file_put_contents($root.'/data.txt', 'original');

        $service = app(BackupService::class);
        $backup = $service->create('manual', $root);
        $this->backupIds[] = $backup->id;

        // Simulate a failed update introducing brand-new files.
        mkdir($root.'/newdir', 0755, true);
        file_put_contents($root.'/newdir/added.txt', 'added by update');
        file_put_contents($root.'/added.php', 'added by update');

        $service->restore($backup, $root);

        $this->assertFileDoesNotExist($root.'/added.php');
        $this->assertFileDoesNotExist($root.'/newdir/added.txt');
        $this->assertSame('original', file_get_contents($root.'/data.txt'));
    }

    public function test_apply_failure_rolls_back_and_restores_files(): void
    {
        $sha = str_repeat('e', 40);

        // Branch tip OK, but the download 404s → failure mid-flight.
        Http::fake([
            'api.github.com/repos/acme/myebookbazaar/branches/main' => Http::response(['commit' => ['sha' => $sha]], 200),
            'codeload.github.com/*' => Http::response('not found', 404),
        ]);

        $root = $this->makeTempDir();
        file_put_contents($root.'/KEEP.txt', 'must survive');

        $service = $this->updateService($root);

        try {
            $service->applyUpdate($sha);
            $this->fail('Expected UpdateException');
        } catch (UpdateException $e) {
            $this->assertStringContainsString('rolled back', $e->getMessage());
        }

        // Files restored from the pre-update backup.
        $this->assertSame('must survive', file_get_contents($root.'/KEEP.txt'));

        // SHA not recorded; failure audited.
        $this->assertNull(app(SettingsService::class)->get('updates.installed_sha'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'update.failed']);

        $backup = Backup::where('type', 'pre-update')->latest('id')->first();
        $this->assertNotNull($backup);
        $this->backupIds[] = $backup->id;
    }

    public function test_apply_success_end_to_end_with_real_import_path(): void
    {
        $sha = str_repeat('d', 40);

        $tarball = $this->makeTarball($sha, [
            'NEWFILE.txt' => 'from the release',
            // Pad well above the 1KB "empty/corrupt download" sanity check.
            'PADDING.bin' => random_bytes(4096),
            // A usable compiled build (the updater validates the manifest).
            'public/build/manifest.json' => json_encode(['new.js' => ['file' => 'assets/new.js']]),
        ]);
        Http::fake([
            'api.github.com/repos/acme/myebookbazaar/branches/main' => Http::response(['commit' => ['sha' => $sha]], 200),
            'api.github.com/repos/acme/myebookbazaar/releases/latest' => Http::response([
                'tag_name' => 'v1.0.1', 'name' => 'Version 1.0.1', 'body' => 'notes', 'prerelease' => false,
            ], 200),
            'codeload.github.com/*' => Http::response(file_get_contents($tarball), 200),
        ]);

        $root = $this->makeTempDir();
        file_put_contents($root.'/OLD.txt', 'original');

        $service = $this->updateService($root);
        $service->applyUpdate($sha);

        $this->assertSame('from the release', file_get_contents($root.'/NEWFILE.txt'));
        $this->assertSame($sha, app(SettingsService::class)->get('updates.installed_sha'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'update.completed']);

        $backup = Backup::where('type', 'pre-update')->latest('id')->first();
        $this->assertNotNull($backup);
        $this->backupIds[] = $backup->id;
    }
}
