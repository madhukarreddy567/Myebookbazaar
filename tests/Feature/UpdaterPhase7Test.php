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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 7: GitHub auto-updater + backups.
 *
 * GitHub is faked via the Http facade. The update apply-flow runs against a
 * temporary fake "project root" so the real tree is never touched.
 */
class UpdaterPhase7Test extends TestCase
{
    use DatabaseTransactions;

    protected SettingsService $settings;

    /** Temp dirs created during the test (cleaned in tearDown). */
    protected array $tmpDirs = [];

    /** Temp files created during the test (cleaned in tearDown). */
    protected array $tmpFiles = [];

    /** Backup files created during the test (cleaned in tearDown). */
    protected array $backupFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->settings = app(SettingsService::class);

        $this->settings->set('updates.enabled', '1');
        $this->settings->set('updates.repo_owner', 'acme');
        $this->settings->set('updates.repo_name', 'myebookbazaar');
        $this->settings->set('updates.branch', 'main');
        $this->settings->set('updates.channel', 'stable');
        $this->settings->set('updates.backup_retention', '7');
        // Token stored encrypted: the settings row is seeded with is_secret.
        $this->settings->set('updates.github_token', 'ghp_testtoken123');
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpDirs as $dir) {
            $this->removeDir($dir);
        }
        foreach ($this->tmpFiles as $file) {
            @unlink($file);
        }
        foreach ($this->backupFiles as $file) {
            Storage::disk('local')->delete(BackupService::DIR.'/'.$file);
        }
        parent::tearDown();
    }

    protected function makeSuperAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Super Admin',
            'email' => 'super-'.uniqid().'@example.com',
            'password' => 'password12345',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    protected function makeStaff(): Admin
    {
        return Admin::create([
            'name' => 'Staff',
            'email' => 'staff-'.uniqid().'@example.com',
            'password' => 'password12345',
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    protected function makeTempDir(): string
    {
        // NOTE: created under the project (not the system temp dir) so the
        // update disk-space guard (>500MB free) sees the real filesystem.
        // The sandbox /tmp is a 512MB tmpfs and sits just under that line.
        $base = storage_path('framework/testing/meb-test');
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

    /** Build a real tar.gz whose top dir matches the GitHub codeload layout. */
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

    /**
     * BackupService stub for transactional tests: the real DB dump/import
     * runs in a mysql subprocess which deadlocks on the test's open
     * transaction. The real import path is covered in
     * UpdaterRestorePhase7Test (no open transaction there).
     */
    protected function updateServiceWithStubBackups(string $root, array &$state): UpdateService
    {
        $stub = new class($state, $this->settings) extends BackupService {
            public array $state;
            public function __construct(&$state, SettingsService $settings)
            {
                $this->state = &$state;
                parent::__construct($settings);
            }
            public function create(string $type = 'manual', ?string $root = null): \App\Models\Backup
            {
                $backup = \App\Models\Backup::create([
                    'filename' => 'stub-'.uniqid().'.zip', 'type' => $type, 'size_bytes' => 1,
                    'created_at' => now('UTC'),
                ]);
                $this->state['created'] = $backup;

                return $backup;
            }
            public function restore(\App\Models\Backup $backup, ?string $root = null): void
            {
                $this->state['restore_called'] = true;
            }
        };

        return new UpdateService(
            app(SettingsService::class),
            app(GitHubService::class),
            $stub,
            $root,
        );
    }

    protected function fakeGithubOk(string $sha): void
    {
        Http::fake([
            'api.github.com/repos/acme/myebookbazaar/branches/main' => Http::response(['commit' => ['sha' => $sha]], 200),
            'api.github.com/repos/acme/myebookbazaar/releases/latest' => Http::response([
                'tag_name' => 'v1.0.1',
                'name' => 'Version 1.0.1',
                'body' => "Bug fixes.\n\n- Faster checkout",
                'prerelease' => false,
            ], 200),
            'api.github.com/repos/acme/myebookbazaar' => Http::response(['full_name' => 'acme/myebookbazaar'], 200),
        ]);
    }

    // ------------------------------------------------------------------
    // Settings UI + Test Connection
    // ------------------------------------------------------------------

    public function test_updates_settings_tab_renders(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/reddy/settings?tab=updates')
            ->assertOk()
            ->assertSee('Repository owner')
            ->assertSee('GitHub fine-grained token')
            ->assertSee('Test GitHub connection');
    }

    public function test_github_test_connection_success_and_failure(): void
    {
        $admin = $this->makeSuperAdmin();

        // Note: Http::fake() appends stubs, so the two responses are queued
        // as a sequence on the same URL pattern.
        Http::fake([
            'api.github.com/repos/acme/myebookbazaar' => Http::sequence()
                ->push(['full_name' => 'acme/myebookbazaar'], 200)
                ->push(['message' => 'Bad credentials'], 401),
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson('/reddy/settings/test-github')
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($admin, 'admin')
            ->postJson('/reddy/settings/test-github')
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.test_github']);
    }

    public function test_token_is_stored_encrypted_and_never_leaked(): void
    {
        $row = \App\Models\Setting::where('key', 'updates.github_token')->first();
        $this->assertNotNull($row);
        $this->assertTrue((bool) $row->is_secret);
        // Ciphertext in the DB, never the plaintext.
        $this->assertStringNotContainsString('ghp_testtoken123', (string) $row->value);

        // Decrypts correctly through the service.
        $this->assertSame('ghp_testtoken123', $this->settings->get('updates.github_token'));

        // Not leaked in the settings page HTML.
        $admin = $this->makeSuperAdmin();
        $html = $this->actingAs($admin, 'admin')->get('/reddy/settings?tab=updates')->getContent();
        $this->assertStringNotContainsString('ghp_testtoken123', $html);
    }

    // ------------------------------------------------------------------
    // Check for updates
    // ------------------------------------------------------------------

    public function test_check_for_updates_detects_newer_sha(): void
    {
        $sha = str_repeat('b', 40);
        $this->fakeGithubOk($sha);
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/reddy/updates/check')
            ->assertRedirect('/reddy/updates');

        $this->assertSame($sha, $this->settings->get('updates.latest_sha'));
        $this->assertSame('v1.0.1', $this->settings->get('updates.release_tag'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'update.checked']);
    }

    public function test_updates_page_and_backups_page_require_super_admin(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff, 'admin')->get('/reddy/updates')->assertForbidden();
        $this->actingAs($staff, 'admin')->get('/reddy/backups')->assertForbidden();
        $this->actingAs($staff, 'admin')->post('/reddy/updates/check')->assertForbidden();
    }

    public function test_updates_pages_load_for_super_admin(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin, 'admin')->get('/reddy/updates')->assertOk()->assertSee('Installed version');
        $this->actingAs($admin, 'admin')->get('/reddy/backups')->assertOk()->assertSee('Backups');
    }

    // ------------------------------------------------------------------
    // Guards
    // ------------------------------------------------------------------

    public function test_apply_refused_when_updates_disabled(): void
    {
        $this->settings->set('updates.enabled', '0');
        $service = $this->updateService($this->makeTempDir());

        $this->expectException(UpdateException::class);
        $this->expectExceptionMessageMatches('/disabled/');
        $service->applyUpdate(str_repeat('c', 40));
    }

    public function test_apply_refused_without_token(): void
    {
        $this->settings->set('updates.github_token', null);

        $service = $this->updateService($this->makeTempDir());

        $this->expectException(UpdateException::class);
        $this->expectExceptionMessageMatches('/token/');
        $service->applyUpdate(str_repeat('c', 40));
    }

    public function test_apply_refused_when_another_update_in_progress(): void
    {
        $lock = Cache::lock(UpdateService::LOCK_KEY, 3600);
        $this->assertTrue($lock->acquire());

        try {
            $service = $this->updateService($this->makeTempDir());
            $service->applyUpdate(str_repeat('c', 40));
            $this->fail('Expected UpdateException');
        } catch (UpdateException $e) {
            $this->assertStringContainsString('already in progress', $e->getMessage());
        } finally {
            $lock->release();
        }
    }

    // ------------------------------------------------------------------
    // Full apply + rollback against a fake project root
    // ------------------------------------------------------------------

    public function test_apply_update_success_copies_files_and_records_sha(): void
    {
        $sha = str_repeat('d', 40);

        $tarball = $this->makeTarball($sha, [
            'NEWFILE.txt' => 'from the release',
            'version.txt' => '1.0.1',
            // Pad well above the 1KB "empty/corrupt download" sanity check.
            'PADDING.bin' => random_bytes(4096),
            // A usable compiled build (the updater validates the manifest).
            'public/build/manifest.json' => json_encode(['new.js' => ['file' => 'assets/new.js']]),
            'public/build/assets/new.js' => 'console.log("new");',
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

        $state = [];
        $service = $this->updateServiceWithStubBackups($root, $state);
        $service->applyUpdate($sha);

        // A pre-update backup was taken first.
        $this->assertNotNull($state['created'] ?? null);
        $this->assertSame('pre-update', $state['created']->type);

        // New files landed, old files preserved (copy-over, no wipe).
        $this->assertSame('from the release', file_get_contents($root.'/NEWFILE.txt'));
        $this->assertFileExists($root.'/OLD.txt');

        // The release's compiled build was accepted (valid manifest).
        $manifest = json_decode((string) file_get_contents($root.'/public/build/manifest.json'), true);
        $this->assertArrayHasKey('new.js', $manifest);

        // SHA + version recorded.
        $this->assertSame($sha, app(SettingsService::class)->get('updates.installed_sha'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'update.completed']);
    }

    public function test_update_keeps_old_build_when_release_build_is_broken(): void
    {
        $sha = str_repeat('f', 40);

        // Release tarball WITHOUT a usable public/build.
        $tarball = $this->makeTarball($sha, [
            'NEWFILE.txt' => 'from the release',
            'PADDING.bin' => random_bytes(4096),
        ]);
        Http::fake([
            'api.github.com/repos/acme/myebookbazaar/branches/main' => Http::response(['commit' => ['sha' => $sha]], 200),
            'api.github.com/repos/acme/myebookbazaar/releases/latest' => Http::response([
                'tag_name' => 'v1.0.1', 'name' => 'Version 1.0.1', 'body' => 'notes', 'prerelease' => false,
            ], 200),
            'codeload.github.com/*' => Http::response(file_get_contents($tarball), 200),
        ]);

        $root = $this->makeTempDir();
        mkdir($root.'/public/build', 0755, true);
        file_put_contents(
            $root.'/public/build/manifest.json',
            json_encode(['old.js' => ['file' => 'assets/old.js']])
        );

        $state = [];
        $service = $this->updateServiceWithStubBackups($root, $state);
        $service->applyUpdate($sha);

        // Update succeeded and the old compiled build was kept.
        $this->assertSame('from the release', file_get_contents($root.'/NEWFILE.txt'));
        $manifest = json_decode((string) file_get_contents($root.'/public/build/manifest.json'), true);
        $this->assertArrayHasKey('old.js', $manifest);
        $this->assertArrayNotHasKey('new.js', $manifest);
    }

    public function test_apply_failure_is_audited_without_db_import(): void
    {
        $sha = str_repeat('e', 40);

        // Branch tip OK, but the download 404s → failure mid-flight.
        Http::fake([
            'api.github.com/repos/acme/myebookbazaar/branches/main' => Http::response(['commit' => ['sha' => $sha]], 200),
            'codeload.github.com/*' => Http::response('not found', 404),
        ]);

        $root = $this->makeTempDir();
        file_put_contents($root.'/KEEP.txt', 'must survive');

        // NOTE: the full rollback path (which imports the DB via a mysql
        // subprocess) is covered in UpdaterRestorePhase7Test — inside a
        // DatabaseTransactions test the import would deadlock on the open
        // transaction's metadata locks. Here we use a stub BackupService to
        // prove the rollback is ATTEMPTED and the failure is audited.
        $state = [];
        $service = $this->updateServiceWithStubBackups($root, $state);

        try {
            $service->applyUpdate($sha);
            $this->fail('Expected UpdateException');
        } catch (UpdateException $e) {
            $this->assertStringContainsString('rolled back', $e->getMessage());
        }

        $this->assertTrue($state['restore_called'] ?? false, 'Rollback restore was not attempted');
        $this->assertDatabaseHas('audit_logs', ['action' => 'update.failed']);
    }

    // ------------------------------------------------------------------
    // Backups (no DB import here — import tests live in
    // UpdaterRestorePhase7Test, because a mysql subprocess cannot import
    // while a test transaction holds metadata locks)
    // ------------------------------------------------------------------

    public function test_manual_backup_creates_zip_with_db_and_files(): void
    {
        $admin = $this->makeSuperAdmin();
        $root = $this->makeTempDir();
        file_put_contents($root.'/hello.txt', 'hi');

        // Exercise through the real service (DB dump runs against dev MySQL).
        $service = app(BackupService::class);
        $backup = $service->create('manual', $root);
        $this->backupFiles[] = $backup->filename;

        $this->assertDatabaseHas('backups', ['id' => $backup->id, 'type' => 'manual']);
        $this->assertGreaterThan(0, $backup->size_bytes);

        $zipPath = Storage::disk('local')->path(BackupService::DIR.'/'.$backup->filename);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertContains('database.sql', $names);
        $this->assertContains('files/hello.txt', $names);
        $this->assertNotContains('files/storage/app/backups/'.$backup->filename, $names);

        AuditLog::query()->where('action', 'backup.created')->delete();
        $this->actingAs($admin, 'admin')->post('/reddy/backups')->assertRedirect('/reddy/backups');
        $latest = Backup::latest('id')->first();
        $this->backupFiles[] = $latest->filename;
        $this->assertDatabaseHas('audit_logs', ['action' => 'backup.created']);
    }

    public function test_backup_prune_keeps_retention_count(): void
    {
        $this->settings->set('updates.backup_retention', '2');
        $root = $this->makeTempDir();
        file_put_contents($root.'/x.txt', 'x');

        $service = app(BackupService::class);
        for ($i = 0; $i < 4; $i++) {
            $b = $service->create('manual', $root);
            $this->backupFiles[] = $b->filename;
            sleep(1); // distinct timestamps/filenames
        }

        $remaining = Backup::where('type', 'manual')->count();
        $this->assertSame(2, $remaining);

        // Pre-update backups are never auto-pruned.
        $pre = Backup::create([
            'filename' => 'keep-me.zip', 'type' => 'pre-update', 'size_bytes' => 1,
            'created_at' => now('UTC')->subDays(30),
        ]);
        $service->prune();
        $this->assertDatabaseHas('backups', ['id' => $pre->id]);
    }

    public function test_backup_download_and_delete(): void
    {
        $admin = $this->makeSuperAdmin();
        $root = $this->makeTempDir();
        file_put_contents($root.'/x.txt', 'x');

        $backup = app(BackupService::class)->create('manual', $root);
        $this->backupFiles[] = $backup->filename;

        $this->actingAs($admin, 'admin')
            ->get('/reddy/backups/'.$backup->id.'/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($admin, 'admin')
            ->delete('/reddy/backups/'.$backup->id)
            ->assertRedirect('/reddy/backups');

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
        // Already deleted from disk by the controller; drop from cleanup list.
        $this->backupFiles = array_diff($this->backupFiles, [$backup->filename]);
    }

    // ------------------------------------------------------------------
    // Scheduler commands
    // ------------------------------------------------------------------

    public function test_scheduled_check_command_runs_and_skips_when_disabled(): void
    {
        $sha = str_repeat('f', 40);
        $this->fakeGithubOk($sha);

        $this->artisan('updates:check')->assertSuccessful();
        $this->assertSame($sha, app(SettingsService::class)->get('updates.latest_sha'));

        $this->settings->set('updates.enabled', '0');
        $this->artisan('updates:check')->assertSuccessful();
    }

    public function test_auto_backup_command_creates_backup(): void
    {
        $this->artisan('backups:auto')->assertSuccessful();
        $backup = Backup::where('type', 'auto')->latest('id')->first();
        $this->assertNotNull($backup);
        $this->backupFiles[] = $backup->filename;
    }

    public function test_auto_backup_falls_back_to_config_version_when_setting_empty(): void
    {
        // Regression: config('version') is an array; the fallback must read
        // config('version.version') or the backup crashes with
        // "Array to string conversion".
        app(\App\Services\SettingsService::class)->set('updates.installed_version', null);

        $this->artisan('backups:auto')->assertSuccessful();
        $backup = Backup::where('type', 'auto')->latest('id')->first();
        $this->assertNotNull($backup);
        $this->assertSame('1.0.1', $backup->version);
        $this->backupFiles[] = $backup->filename;
    }

    public function test_admin_route_still_404(): void
    {
        $this->get('/admin')->assertNotFound();
    }
}
