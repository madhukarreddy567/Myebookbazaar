<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 6: settings-driven maintenance mode.
 *
 * When `security.maintenance_enabled` is ON, every public route renders
 * the branded maintenance page (HTTP 503) — except:
 *  - the admin area (/reddy*), so admins can work and switch it off,
 *  - payment webhooks (/webhooks/*), so in-flight payments still settle,
 *  - IPs listed in `security.maintenance_bypass_ips` (one per line).
 *
 * This is intentionally separate from `artisan down`: the state lives in
 * Settings (audited, toggleable from /reddy/settings) and the artisan
 * command `site:maintenance` simply flips the same flag.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! settings_enabled('security.maintenance_enabled')) {
            return $next($request);
        }

        if ($request->is('reddy*') || $request->is('webhooks/*')) {
            return $next($request);
        }

        if ($this->ipIsBypassed($request->ip())) {
            return $next($request);
        }

        return response()->view('errors.503', [
            'customMessage' => trim((string) settings('security.maintenance_message', '')),
        ], 503);
    }

    protected function ipIsBypassed(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        $raw = (string) settings('security.maintenance_bypass_ips', '');
        $list = preg_split('/[\r\n,]+/', $raw) ?: [];

        foreach ($list as $entry) {
            if (trim($entry) === $ip) {
                return true;
            }
        }

        return false;
    }
}
