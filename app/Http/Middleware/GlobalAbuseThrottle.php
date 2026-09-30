<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Closure;

/**
 * Global per-IP request throttle — a last-resort safety net against
 * abusive request floods (credential stuffing bursts, scraper loops,
 * accidental retry storms).
 *
 * 600 requests/minute per IP is deliberately generous: no legitimate
 * browsing session comes close, so real users never notice it.
 *
 * IMPORTANT — this is NOT DDoS protection. True volumetric DDoS
 * absorption happens at the network edge (Cloudflare). The correct
 * production setup is:
 *   1. Cloudflare in front of the app (DNS proxied),
 *   2. TRUSTED_PROXIES=* (or Cloudflare's IP ranges) in .env so
 *      request->ip() below sees the real visitor IP, and
 *   3. a Cloudflare Rate Limiting rule / "Under Attack Mode" for
 *      actual DDoS events.
 * This middleware only catches what slips past the edge.
 *
 * Skipped while running unit tests so the automated suite (hundreds of
 * requests from 127.0.0.1 in one process) is never throttled.
 */
class GlobalAbuseThrottle extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 600, $decayMinutes = 1, $prefix = 'global')
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
