<?php

namespace App\Console\Commands;

use App\Services\SettingsService;
use App\Support\Security;
use Illuminate\Console\Command;

/**
 * Phase 6: flip the settings-driven maintenance flag from the console.
 * Uses the same `security.maintenance_enabled` setting the middleware
 * and the /reddy/settings toggle use, so all three always agree.
 *
 *   php artisan site:maintenance on
 *   php artisan site:maintenance off
 */
class SiteMaintenanceCommand extends Command
{
    protected $signature = 'site:maintenance {state : on|off}';
    protected $description = 'Turn the settings-driven maintenance mode on or off.';

    public function handle(SettingsService $settings): int
    {
        $state = strtolower((string) $this->argument('state'));

        if (! in_array($state, ['on', 'off'], true)) {
            $this->error('State must be "on" or "off".');

            return self::FAILURE;
        }

        $settings->set('security.maintenance_enabled', $state === 'on' ? '1' : '0');

        Security::log(
            'maintenance.'.($state === 'on' ? 'enabled' : 'disabled'),
            'Maintenance mode '.$state.' via artisan command.'
        );

        $this->info('Maintenance mode is now '.strtoupper($state).'.');

        return self::SUCCESS;
    }
}
