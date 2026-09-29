<?php

namespace App\Services;

use App\Support\Audit;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 7: secure GitHub-based auto-updater.
 *
 * Safety rules (all enforced before anything is touched):
 *  - updates.enabled must be ON
 *  - a GitHub token must be configured
 *  - the confirmed SHA must exactly match the API-advertised branch tip
 *  - at least 500 MB of free disk space
 *  - no other update may be in progress (cache lock)
 *
 * Updates are NEVER automatic — an admin clicks, confirms, and the update
 * runs synchronously (shared-hosting friendly). On ANY failure the
 * pre-update backup is restored automatically and the site comes back up.
 */
class UpdateService
{
    public const LOCK_KEY = 'meb.updater.lock';
    public const MIN_FREE_BYTES = 500 * 1024 * 1024;

    protected string $appRoot;

    public function __construct(
        protected SettingsService $settings,
        protected GitHubService $github,
        protected BackupService $backups,
        ?string $appRoot = null,
    ) {
        $this->appRoot = rtrim($appRoot ?? base_path(), DIRECTORY_SEPARATOR);
    }

    // ------------------------------------------------------------------
    // Status & checking
    // ------------------------------------------------------------------

    public function installedVersion(): string
    {
        return (string) ($this->settings->get('updates.installed_version') ?: config('version.version', '0.0.1'));
    }

    public function installedSha(): ?string
    {
        $sha = $this->settings->get('updates.installed_sha');

        return is_string($sha) && $sha !== '' ? $sha : null;
    }

    /**
     * Ask GitHub for the latest commit SHA (+ release info) and remember it.
     *
     * @return array{ok: bool, available: bool, latest_sha: ?string, release: array, message: string}
     */
    public function checkForUpdates(): array
    {
        $result = ['ok' => false, 'available' => false, 'latest_sha' => null, 'release' => [], 'message' => ''];

        if (! $this->updatesEnabled()) {
            $result['message'] = 'Updates are disabled in Settings → Updates.';

            return $result;
        }

        $shaResult = $this->github->latestBranchSha();
        if (! $shaResult['ok']) {
            $result['message'] = $shaResult['message'];
            Audit::record('update.checked', 'settings', null, [], ['result' => 'failed', 'reason' => $shaResult['message']]);

            return $result;
        }

        $latestSha = $shaResult['sha'];
        $installed = $this->installedSha();
        $available = $installed === null || ! hash_equals((string) $installed, (string) $latestSha);

        $release = $this->github->releaseInfo();
        if ($release['ok']) {
            $release['sha'] = $latestSha;
        }

        $this->settings->set('updates.last_checked_at', now('UTC')->toIso8601String());
        $this->settings->set('updates.latest_sha', $latestSha);
        $this->settings->set('updates.release_tag', $release['tag'] ?? null);
        $this->settings->set('updates.release_name', $release['name'] ?? null);
        $this->settings->set('updates.release_notes', $release['notes'] ?? null);

        Audit::record('update.checked', 'settings', null, [], [
            'result' => 'ok',
            'available' => $available ? 'yes' : 'no',
            'latest_sha' => substr((string) $latestSha, 0, 12),
        ]);

        return [
            'ok' => true,
            'available' => $available,
            'latest_sha' => $latestSha,
            'release' => $release['ok'] ? $release : [],
            'message' => $available ? 'An update is available.' : 'You are on the latest commit.',
        ];
    }

    public function lastCheckAt(): ?string
    {
        $value = $this->settings->get('updates.last_checked_at');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function latestKnownSha(): ?string
    {
        $value = $this->settings->get('updates.latest_sha');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function latestRelease(): array
    {
        return [
            'tag' => $this->settings->get('updates.release_tag'),
            'name' => $this->settings->get('updates.release_name'),
            'notes' => $this->settings->get('updates.release_notes'),
        ];
    }

    public function updateInProgress(): bool
    {
        return ! Cache::lock(self::LOCK_KEY, 1)->acquire();
    }

    // ------------------------------------------------------------------
    // Apply an update (with automatic rollback)
    // ------------------------------------------------------------------

    /**
     * Run the full update flow for the given SHA.
     *
     * @throws UpdateException on any failure (rollback already performed).
     */
    public function applyUpdate(string $expectedSha): void
    {
        $guard = $this->guard($expectedSha);
        if ($guard !== null) {
            throw new UpdateException($guard);
        }

        $lock = Cache::lock(self::LOCK_KEY, 3600);
        if (! $lock->acquire()) {
            throw new UpdateException('Another update is already in progress.');
        }

        set_time_limit(900);

        Audit::record('update.started', 'settings', null, [], ['sha' => substr($expectedSha, 0, 12)]);

        // Re-verify the branch tip right before touching anything — the
        // confirmed SHA must be exactly what GitHub advertises NOW.
        $tip = $this->github->latestBranchSha();
        if (! $tip['ok'] || ! hash_equals($expectedSha, (string) $tip['sha'])) {
            $lock->release();
            throw new UpdateException('The update was cancelled: the branch tip changed while you were confirming. Please check again.');
        }

        $backup = null;
        $maintenanceWasOn = settings_enabled('security.maintenance_enabled');

        try {
            // 1. Full backup first.
            $backup = $this->backups->create('pre-update', $this->appRoot);

            // 2. Maintenance mode (Phase 6, settings-driven).
            $this->settings->set('security.maintenance_enabled', true);

            // 3. Download the tarball for the exact SHA.
            $tmpTarball = sys_get_temp_dir().'/meb-update-'.uniqid().'.tar.gz';
            $download = $this->github->downloadTarball($expectedSha, $tmpTarball);
            if (! $download['ok']) {
                throw new UpdateException($download['message']);
            }

            // 4. Extract and verify the payload belongs to this SHA.
            $tmpExtract = sys_get_temp_dir().'/meb-extract-'.uniqid();
            mkdir($tmpExtract, 0755, true);
            $this->extractTarball($tmpTarball, $tmpExtract);
            @unlink($tmpTarball);

            $payloadDir = $this->findPayloadDir($tmpExtract, $expectedSha);
            if ($payloadDir === null) {
                $this->removeDir($tmpExtract);
                throw new UpdateException('The downloaded archive did not match the expected commit. Aborting.');
            }

            // 5. Copy over the project, preserving environment + user data.
            // Stash the current compiled build first: if the release's
            // build is missing or broken, the old one is put back (this
            // project ships committed compiled assets per UPDATING.md).
            $buildDir = $this->appRoot.'/public/build';
            $buildStash = null;
            if (is_dir($buildDir)) {
                // Copy (not rename): the stash may live on another filesystem.
                $buildStash = sys_get_temp_dir().'/meb-build-'.uniqid();
                $this->backups->copyTree($buildDir, $buildStash);
                $this->removeDir($buildDir);
            }

            $this->backups->copyTree($payloadDir, $this->appRoot, [
                '.env',
                '.env.example',
                '.env.backup',
                'storage/app',        // private PDFs, uploads, backups
                'storage/logs',
            ]);
            $this->removeDir($tmpExtract);

            $this->ensureValidBuild($buildDir, $buildStash);

            // 6. Migrate, then clear caches.
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            // 7. Record the new version + SHA.
            $this->settings->set('updates.installed_version', $this->latestRelease()['tag'] ?? $this->installedVersion());
            $this->settings->set('updates.installed_sha', $expectedSha);

            Audit::record('update.completed', 'settings', null, [], [
                'sha' => substr($expectedSha, 0, 12),
                'backup' => $backup->filename,
            ]);
        } catch (\Throwable $e) {
            // 8. Automatic rollback on ANY failure.
            try {
                if ($backup instanceof \App\Models\Backup) {
                    $this->backups->restore($backup, $this->appRoot);

                    // The DB restore wipes the pre-update backup's own row
                    // (the dump is taken before the row is created), so
                    // re-record it — the zip on disk survived the restore.
                    \App\Models\Backup::create([
                        'filename' => $backup->filename,
                        'type' => $backup->type,
                        'size_bytes' => $backup->size_bytes,
                        'version' => $backup->version,
                        'commit_sha' => $backup->commit_sha,
                        'notes' => 'Re-recorded after automatic rollback.',
                        'created_at' => $backup->created_at,
                    ]);
                }
            } catch (\Throwable $rollbackError) {
                report($rollbackError);
            }

            Audit::record('update.failed', 'settings', null, [], [
                'sha' => substr($expectedSha, 0, 12),
                'error' => mb_substr($e->getMessage(), 0, 500),
                'rolled_back' => $backup ? 'yes' : 'no',
            ]);

            throw new UpdateException('Update failed and was rolled back: '.$e->getMessage(), 0, $e);
        } finally {
            if (! $maintenanceWasOn) {
                $this->settings->set('security.maintenance_enabled', false);
            }
            $lock->release();
        }
    }

    /**
     * Pre-flight checks. Returns an error message, or null when clear.
     */
    public function guard(?string $expectedSha = null): ?string
    {
        if (! $this->updatesEnabled()) {
            return 'Updates are disabled in Settings → Updates.';
        }

        if (! $this->github->token()) {
            return 'No GitHub token configured. Add one in Settings → Updates.';
        }

        if (! $this->github->configured()) {
            return 'Repository owner/name not configured in Settings → Updates.';
        }

        if ($expectedSha !== null && ! preg_match('/^[0-9a-f]{40}$/i', $expectedSha)) {
            return 'Invalid commit SHA.';
        }

        $free = @disk_free_space($this->appRoot);
        if ($free !== false && $free < self::MIN_FREE_BYTES) {
            return 'Not enough free disk space (need 500 MB).';
        }

        return null;
    }

    public function updatesEnabled(): bool
    {
        return settings_enabled('updates.enabled');
    }

    public function freeSpaceLabel(): string
    {
        $free = @disk_free_space($this->appRoot);
        if ($free === false) {
            return 'unknown';
        }

        return $free >= 1073741824
            ? round($free / 1073741824, 1).' GB free'
            : round($free / 1048576).' MB free';
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    protected function extractTarball(string $tarball, string $target): void
    {
        try {
            $phar = new \PharData($tarball);
            $phar->extractTo($target, null, true);
        } catch (\Throwable $e) {
            throw new UpdateException('Could not extract the update archive: '.$e->getMessage());
        }
    }

    /**
     * GitHub tarballs wrap everything in one top-level directory named
     * {owner}-{repo}-{sha-short}. Confirm the extracted payload matches
     * the expected SHA prefix before copying anything.
     */
    protected function findPayloadDir(string $extractDir, string $expectedSha): ?string
    {
        $entries = array_values(array_filter(
            scandir($extractDir) ?: [],
            fn ($e) => $e !== '.' && $e !== '..' && is_dir($extractDir.'/'.$e)
        ));

        if (count($entries) !== 1) {
            return null;
        }

        $dir = $entries[0];
        $short = substr($expectedSha, 0, 7);

        // codeload names it "{repo}-{full-or-short-sha}" — accept either.
        if (! str_ends_with($dir, '-'.$expectedSha) && ! str_ends_with($dir, '-'.$short)) {
            return null;
        }

        return $extractDir.'/'.$dir;
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

    /**
     * Make sure public/build has a usable Vite manifest after the copy-over.
     * Prefers the release's committed build; falls back to the stashed old
     * build when the new one is missing or broken. Throws when neither is
     * usable (the caller rolls back).
     */
    protected function ensureValidBuild(string $buildDir, ?string $buildStash): void
    {
        if ($this->buildManifestValid($buildDir.'/manifest.json')) {
            if ($buildStash !== null) {
                $this->removeDir($buildStash);
            }

            return;
        }

        // New build unusable — put the old one back when we have it.
        if ($buildStash !== null && is_dir($buildStash)) {
            $this->removeDir($buildDir);
            $this->backups->copyTree($buildStash, $buildDir);
            $this->removeDir($buildStash);

            return;
        }

        throw new UpdateException('The release has no usable compiled assets (public/build/manifest.json missing or broken).');
    }

    protected function buildManifestValid(string $manifestPath): bool
    {
        if (! is_file($manifestPath)) {
            return false;
        }
        $data = json_decode((string) file_get_contents($manifestPath), true);

        return is_array($data) && $data !== [];
    }
}
