<?php

namespace App\Console\Commands;

use App\Services\UpdateService;
use Illuminate\Console\Command;

/**
 * Phase 7: scheduled update check (every 6h via the scheduler).
 * Only CHECKS — it never downloads or applies anything.
 */
class CheckForUpdates extends Command
{
    protected $signature = 'updates:check';
    protected $description = 'Check GitHub for a newer commit on the tracked branch (never applies).';

    public function handle(UpdateService $updates): int
    {
        if (! $updates->updatesEnabled()) {
            $this->line('Updates disabled — skipping.');

            return self::SUCCESS;
        }

        if (! settings_enabled('updates.auto_check', true)) {
            $this->line('Automatic checks disabled — skipping.');

            return self::SUCCESS;
        }

        $result = $updates->checkForUpdates();

        $this->line($result['message']);
        if ($result['ok'] && $result['available']) {
            $this->line('Latest SHA: '.substr((string) $result['latest_sha'], 0, 12));
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
