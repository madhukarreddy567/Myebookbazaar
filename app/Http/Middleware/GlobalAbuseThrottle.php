<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global per-IP request throttle — a last-resort safety net against
 * abusive request floods (credential stuffing bursts, scraper loops,
 * accidental retry storms).
 *
 * 600 requests/minute per IP is deliberately generous: no legitimate
 * browsing session comes close, so real users never notice it.
 *
 * This middleware runs in the GLOBAL stack, i.e. BEFORE the router
 * resolves the request to a route. It therefore MUST NOT rely on
 * $request->route() or the authenticated user to build its throttle
 * key (Laravel's own ThrottleRequests throws "Unable to generate the
 * request signature. Route unavailable." in exactly this situation —
 * which took the whole site down with a 500 on every page).
 * The key is derived from the visitor IP only.
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
class GlobalAbuseThrottle
{
    public function __construct(protected RateLimiter $limiter) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): Response  $next
     */
    public function handle($request, Closure $next, int $maxAttempts = 600, int $decayMinutes = 1): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $key = $this->resolveKey($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw new ThrottleRequestsException('Too many requests.');
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        /** @var Response $response */
        $response = $next($request);

        return $this->addHeaders($response, $maxAttempts, $this->limiter->retriesLeft($key, $maxAttempts));
    }

    /**
     * IP-only key: safe to compute before routing.
     */
    protected function resolveKey($request): string
    {
        return 'global-abuse-throttle|'.sha1((string) $request->ip());
    }

    protected function shouldSkip($request): bool
    {
        return app()->runningUnitTests();
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remaining): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));

        return $response;
    }
}
