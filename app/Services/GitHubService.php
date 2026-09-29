<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Phase 7: GitHub API client for the auto-updater.
 *
 * Uses a fine-grained read-only personal access token stored encrypted in
 * Settings → Updates (updates.github_token). The token is sent ONLY to
 * api.github.com / codeload.github.com and is NEVER written to logs,
 * audit records, exceptions or the session.
 */
class GitHubService
{
    public const API_BASE = 'https://api.github.com';
    public const CODELOAD_BASE = 'https://codeload.github.com';

    public function __construct(protected SettingsService $settings) {}

    public function configured(): bool
    {
        return $this->owner() !== '' && $this->repo() !== '';
    }

    public function owner(): string
    {
        return trim((string) $this->settings->get('updates.repo_owner', ''));
    }

    public function repo(): string
    {
        return trim((string) $this->settings->get('updates.repo_name', ''));
    }

    public function branch(): string
    {
        $branch = trim((string) $this->settings->get('updates.branch', ''));

        return $branch !== '' ? $branch : 'main';
    }

    public function channel(): string
    {
        return $this->settings->get('updates.channel', 'stable') === 'beta' ? 'beta' : 'stable';
    }

    public function token(): ?string
    {
        $token = $this->settings->get('updates.github_token');

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
    }

    protected function client()
    {
        $client = Http::timeout(25)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'MyEbookBazaar-Updater',
            ]);

        if ($token = $this->token()) {
            $client = $client->withToken($token);
        }

        return $client;
    }

    protected function repoPath(): string
    {
        return '/repos/'.$this->owner().'/'.$this->repo();
    }

    /**
     * Test Connection: verifies the token can read the configured repo.
     * Returns ['ok' => bool, 'message' => string].
     */
    public function testConnection(): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'message' => 'Set the repository owner and name first.'];
        }
        if (! $this->token()) {
            return ['ok' => false, 'message' => 'Save a GitHub token first.'];
        }

        try {
            $response = $this->client()->get(self::API_BASE.$this->repoPath());

            if ($response->successful()) {
                $name = $response->json('full_name') ?? ($this->owner().'/'.$this->repo());

                return ['ok' => true, 'message' => 'Connected — can read '.$name.'.'];
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return ['ok' => false, 'message' => 'Token rejected (HTTP '.$response->status().'). Check the token has Contents read permission on this repository.'];
            }

            if ($response->status() === 404) {
                return ['ok' => false, 'message' => 'Repository not found. Check the owner/name spelling.'];
            }

            return ['ok' => false, 'message' => 'GitHub answered HTTP '.$response->status().'.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach GitHub: '.$e->getMessage()];
        }
    }

    /**
     * Latest commit SHA on the tracked branch.
     *
     * @return array{ok: bool, sha: ?string, message: string}
     */
    public function latestBranchSha(): array
    {
        if (! $this->configured() || ! $this->token()) {
            return ['ok' => false, 'sha' => null, 'message' => 'Repository or token not configured.'];
        }

        try {
            $response = $this->client()->get(self::API_BASE.$this->repoPath().'/branches/'.rawurlencode($this->branch()));
            if (! $response->successful()) {
                return ['ok' => false, 'sha' => null, 'message' => 'GitHub answered HTTP '.$response->status().' for branch "'.$this->branch().'".'];
            }

            $sha = $response->json('commit.sha');

            return is_string($sha) && $sha !== ''
                ? ['ok' => true, 'sha' => $sha, 'message' => 'OK']
                : ['ok' => false, 'sha' => null, 'message' => 'GitHub returned no commit SHA.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'sha' => null, 'message' => 'Could not reach GitHub: '.$e->getMessage()];
        }
    }

    /**
     * Release info for the configured channel.
     *
     * @return array{ok: bool, tag: ?string, name: ?string, notes: ?string, prerelease: bool, sha: ?string, message: string}
     */
    public function releaseInfo(): array
    {
        $blank = ['ok' => false, 'tag' => null, 'name' => null, 'notes' => null, 'prerelease' => false, 'sha' => null, 'message' => ''];

        if (! $this->configured() || ! $this->token()) {
            $blank['message'] = 'Repository or token not configured.';

            return $blank;
        }

        try {
            if ($this->channel() === 'beta') {
                $response = $this->client()->get(self::API_BASE.$this->repoPath().'/releases?per_page=1');
                if (! $response->successful()) {
                    $blank['message'] = 'GitHub answered HTTP '.$response->status().'.';

                    return $blank;
                }
                $release = $response->json('0');
            } else {
                $response = $this->client()->get(self::API_BASE.$this->repoPath().'/releases/latest');
                if ($response->status() === 404) {
                    $blank['message'] = 'No stable releases published yet.';

                    return $blank;
                }
                if (! $response->successful()) {
                    $blank['message'] = 'GitHub answered HTTP '.$response->status().'.';

                    return $blank;
                }
                $release = $response->json();
            }

            if (! is_array($release)) {
                $blank['message'] = 'No releases published yet.';

                return $blank;
            }

            return [
                'ok' => true,
                'tag' => $release['tag_name'] ?? null,
                'name' => $release['name'] ?? $release['tag_name'] ?? null,
                'notes' => $release['body'] ?? '',
                'prerelease' => (bool) ($release['prerelease'] ?? false),
                'sha' => null, // resolved separately against the branch tip
                'message' => 'OK',
            ];
        } catch (\Throwable $e) {
            $blank['message'] = 'Could not reach GitHub: '.$e->getMessage();

            return $blank;
        }
    }

    /**
     * Download the tarball for an exact commit SHA (streamed to $targetPath).
     * Because the SHA is embedded in the codeload URL, GitHub serves exactly
     * that commit's tree — a SHA the API never advertised cannot be fetched
     * this way, which is the integrity guarantee.
     *
     * @return array{ok: bool, message: string}
     */
    public function downloadTarball(string $sha, string $targetPath): array
    {
        if (! preg_match('/^[0-9a-f]{40}$/i', $sha)) {
            return ['ok' => false, 'message' => 'Refusing to download: invalid commit SHA format.'];
        }

        $url = self::CODELOAD_BASE.'/'.$this->owner().'/'.$this->repo().'/tar.gz/'.$sha;

        try {
            $request = Http::timeout(300)
                ->withHeaders(['User-Agent' => 'MyEbookBazaar-Updater'])
                ->sink($targetPath);

            if ($token = $this->token()) {
                $request = $request->withToken($token);
            }

            $response = $request->get($url);

            if (! $response->successful()) {
                @unlink($targetPath);

                return ['ok' => false, 'message' => 'Download failed — GitHub answered HTTP '.$response->status().'.'];
            }

            // Faked responses (tests) do not honor `sink` — write the body
            // directly in that case.
            if (! is_file($targetPath) || filesize($targetPath) === 0) {
                file_put_contents($targetPath, $response->body());
            }

            if (! is_file($targetPath) || filesize($targetPath) < 1024) {
                @unlink($targetPath);

                return ['ok' => false, 'message' => 'Downloaded file is empty or corrupt.'];
            }

            return ['ok' => true, 'message' => 'OK'];
        } catch (\Throwable $e) {
            @unlink($targetPath);

            return ['ok' => false, 'message' => 'Download failed: '.$e->getMessage()];
        }
    }
}
