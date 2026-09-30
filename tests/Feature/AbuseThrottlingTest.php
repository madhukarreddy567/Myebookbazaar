<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalAbuseThrottle;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Abuse hardening: rate limits beyond auth.
 *
 * - Public search/filter listings (/books, /combos, /offers): throttle:60,1
 * - Sitemap (DB-heavy, scraper target): throttle:30,1
 * - Signed download route: throttle:30,1 after the signature check
 * - Global per-IP safety net: GlobalAbuseThrottle (600/min), skipped in tests
 *
 * NOTE: true DDoS absorption belongs at the Cloudflare edge, not in
 * Laravel — see GlobalAbuseThrottle's docblock for the recommended setup.
 */
class AbuseThrottlingTest extends TestCase
{
    /** Make a request appearing to come from a dedicated IP so the test's
     *  throttle bucket is isolated from every other test in the suite. */
    private function getAsIp(string $uri, string $ip)
    {
        return $this->call('GET', $uri, [], [], [], ['REMOTE_ADDR' => $ip]);
    }

    public function test_public_book_search_is_throttled_per_ip(): void
    {
        $ip = '203.0.113.10';

        for ($i = 0; $i < 60; $i++) {
            $this->getAsIp('/books', $ip)->assertOk();
        }

        // 61st request inside the minute is rejected.
        $this->getAsIp('/books', $ip)->assertStatus(429);
    }

    public function test_sitemap_is_throttled(): void
    {
        $ip = '203.0.113.11';

        for ($i = 0; $i < 30; $i++) {
            $this->getAsIp('/sitemap.xml', $ip)->assertOk();
        }

        $this->getAsIp('/sitemap.xml', $ip)->assertStatus(429);
    }

    public function test_search_routes_carry_throttle_middleware(): void
    {
        $this->assertContains(
            'throttle:60,1',
            Route::getRoutes()->getByName('books')->gatherMiddleware()
        );
        $this->assertContains(
            'throttle:60,1',
            Route::getRoutes()->getByName('combos')->gatherMiddleware()
        );
        $this->assertContains(
            'throttle:60,1',
            Route::getRoutes()->getByName('offers')->gatherMiddleware()
        );
        $this->assertContains(
            'throttle:30,1',
            Route::getRoutes()->getByName('sitemap')->gatherMiddleware()
        );
    }

    public function test_download_route_is_throttled_after_signature_check(): void
    {
        $middleware = Route::getRoutes()->getByName('download')->gatherMiddleware();

        $this->assertContains('signed', $middleware);
        $this->assertContains('throttle:30,1', $middleware);

        // Throttle must run AFTER the signature check so only valid,
        // entitled downloads consume the file-serving budget.
        $this->assertGreaterThan(
            array_search('signed', $middleware),
            array_search('throttle:30,1', $middleware)
        );
    }

    public function test_global_abuse_throttle_is_registered_globally(): void
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        $this->assertContains(GlobalAbuseThrottle::class, $kernel->getGlobalMiddleware());
    }

    public function test_global_abuse_throttle_passes_through_in_unit_tests(): void
    {
        // In the test environment the safety net must never interfere
        // with the suite (hundreds of requests from 127.0.0.1).
        $this->assertTrue(app()->runningUnitTests());

        $middleware = new GlobalAbuseThrottle(app(RateLimiter::class));
        $request = Request::create('/', 'GET');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
        // No rate-limit headers: the limiter was never consulted.
        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
    }
}
