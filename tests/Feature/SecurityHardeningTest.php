<?php

namespace Tests\Feature;

use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regression tests for the security-hardening audit (2026-09-29):
 *
 * - SecurityHeaders middleware: baseline headers, Permissions-Policy,
 *   and HSTS gated strictly on HTTPS (never on plain HTTP).
 * - CSRF: VerifyCsrfToken (instantiated directly, since Laravel skips
 *   CSRF verification while unit testing) rejects token-less POST/PUT/
 *   DELETE requests, accepts a valid token, and keeps exactly one
 *   justified exception: the HMAC-signed Razorpay webhook.
 * - Session cookie flags: HttpOnly + SameSite=Lax always; Secure
 *   auto-detected from APP_URL (https => Secure) unless
 *   SESSION_SECURE_COOKIE is set explicitly.
 * - Production config sanity: APP_DEBUG must not default to true.
 */
class SecurityHardeningTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    // ------------------------------------------------------------------
    // Security headers
    // ------------------------------------------------------------------

    public function test_baseline_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotEmpty(
            $response->headers->get('Content-Security-Policy'),
            'Content-Security-Policy header must be set.'
        );
        $this->assertNotEmpty(
            $response->headers->get('Permissions-Policy'),
            'Permissions-Policy header must be set.'
        );
    }

    public function test_permissions_policy_denies_sensitive_features(): void
    {
        $policy = (string) $this->get('/')->headers->get('Permissions-Policy');

        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $https = $this->get('https://localhost/');
        $this->assertNotEmpty(
            $https->headers->get('Strict-Transport-Security'),
            'HSTS must be sent on HTTPS responses.'
        );
        $this->assertStringContainsString(
            'max-age=31536000',
            (string) $https->headers->get('Strict-Transport-Security')
        );

        $http = $this->get('http://localhost/');
        $this->assertNull(
            $http->headers->get('Strict-Transport-Security'),
            'HSTS must NEVER be sent over plain HTTP.'
        );
    }

    // ------------------------------------------------------------------
    // CSRF (middleware-level: Laravel deliberately skips CSRF
    // verification while running unit tests, so these instantiate the
    // middleware directly instead of going through HTTP calls)
    // ------------------------------------------------------------------

    /**
     * VerifyCsrfToken with the "skip while unit testing" shortcut
     * disabled, so the tests exercise the real verification logic.
     */
    protected function csrfMiddleware(): VerifyCsrfToken
    {
        return new class($this->app, $this->app['encrypter']) extends VerifyCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };
    }

    protected function csrfRequest(string $method, string $uri, array $params = []): Request
    {
        $request = Request::create($uri, $method, $params);
        $request->setLaravelSession($this->app['session.store']);

        return $request;
    }

    #[DataProvider('stateChangingRequestsProvider')]
    public function test_csrf_middleware_rejects_token_less_requests(string $method, string $uri): void
    {
        $this->expectException(TokenMismatchException::class);

        $this->csrfMiddleware()->handle(
            $this->csrfRequest($method, $uri),
            fn () => response('ok')
        );
    }

    public static function stateChangingRequestsProvider(): array
    {
        return [
            'guest add to cart' => ['POST', '/cart'],
            'remove from cart' => ['DELETE', '/cart/1'],
            'contact form' => ['POST', '/contact'],
            'customer login' => ['POST', '/login'],
            'customer registration' => ['POST', '/register'],
            'forgot password' => ['POST', '/forgot-password'],
            'apply coupon' => ['POST', '/checkout/coupon'],
            'update profile' => ['PUT', '/account/profile'],
            'delete account' => ['DELETE', '/account'],
        ];
    }

    public function test_csrf_middleware_accepts_a_valid_token(): void
    {
        $this->app['session.store']->put('_token', 'test-csrf-token');

        $response = $this->csrfMiddleware()->handle(
            $this->csrfRequest('POST', '/cart', ['_token' => 'test-csrf-token']),
            fn () => response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_csrf_exception_list_is_minimal_and_justified(): void
    {
        // The withMiddleware() callback in bootstrap/app.php only runs
        // when the HTTP kernel is first resolved — trigger it so the
        // exception list reflects the real application configuration.
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        // The ONLY CSRF exception must be the Razorpay webhook, which is
        // called server-to-server and proves authenticity with an HMAC
        // webhook signature instead of a session token.
        $property = new \ReflectionProperty(VerifyCsrfToken::class, 'neverVerify');
        $property->setAccessible(true);

        $this->assertSame(['webhooks/razorpay'], $property->getValue());

        // And the exempted route really does pass through without a token.
        $response = $this->csrfMiddleware()->handle(
            $this->csrfRequest('POST', 'webhooks/razorpay'),
            fn () => response('webhook-ok')
        );

        $this->assertSame('webhook-ok', $response->getContent());
    }

    public function test_razorpay_webhook_is_exempt_from_csrf_but_rejects_bad_signatures(): void
    {
        // No CSRF token: must NOT be a 419 (the route is a justified
        // exception — Razorpay signs server-to-server instead).
        $response = $this->post('/webhooks/razorpay', [], [
            'X-Razorpay-Signature' => 'bogus',
        ]);

        $this->assertSame(
            400,
            $response->getStatusCode(),
            'The webhook must answer 400 for a bad signature, never 419.'
        );
    }

    // ------------------------------------------------------------------
    // Session cookie flags
    // ------------------------------------------------------------------

    public function test_session_cookie_has_safe_flags(): void
    {
        $this->assertTrue(config('session.http_only'), 'Session cookie must be HttpOnly.');
        $this->assertSame('lax', config('session.same_site'), 'Session cookie SameSite must be lax.');
    }

    public function test_session_secure_defaults_to_on_when_app_url_is_https(): void
    {
        $config = $this->loadSessionConfigWithEnv(
            ['APP_URL' => 'https://myebookbazaar-production.up.railway.app'],
            ['SESSION_SECURE_COOKIE']
        );

        $this->assertTrue(
            $config['secure'],
            'Session cookie must default to Secure when APP_URL is https.'
        );
    }

    public function test_session_secure_defaults_to_off_for_plain_http(): void
    {
        $config = $this->loadSessionConfigWithEnv(
            ['APP_URL' => 'http://localhost:8000'],
            ['SESSION_SECURE_COOKIE']
        );

        $this->assertFalse(
            $config['secure'],
            'Session cookie must default to non-Secure for plain-http local dev.'
        );
    }

    public function test_explicit_session_secure_cookie_setting_wins(): void
    {
        $config = $this->loadSessionConfigWithEnv([
            'APP_URL' => 'https://myebookbazaar-production.up.railway.app',
            'SESSION_SECURE_COOKIE' => 'false',
        ]);

        $this->assertFalse(
            $config['secure'],
            'An explicit SESSION_SECURE_COOKIE=false must override the https auto-detection.'
        );
    }

    /**
     * Re-evaluate config/session.php under controlled env vars (the
     * Secure default is computed at config load time).
     */
    protected function loadSessionConfigWithEnv(array $set = [], array $unset = []): array
    {
        $backup = [];
        foreach (array_merge(array_keys($set), $unset) as $key) {
            $backup[$key] = [
                'server' => $_SERVER[$key] ?? null,
                'env' => $_ENV[$key] ?? null,
                'has_server' => array_key_exists($key, $_SERVER),
                'has_env' => array_key_exists($key, $_ENV),
            ];
        }

        try {
            foreach ($unset as $key) {
                unset($_SERVER[$key], $_ENV[$key]);
                putenv($key);
            }
            foreach ($set as $key => $value) {
                $_SERVER[$key] = $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }

            return require config_path('session.php');
        } finally {
            foreach ($backup as $key => $old) {
                if ($old['has_server']) {
                    $_SERVER[$key] = $old['server'];
                } else {
                    unset($_SERVER[$key]);
                }
                if ($old['has_env']) {
                    $_ENV[$key] = $old['env'];
                } else {
                    unset($_ENV[$key]);
                }
                if ($old['has_server'] || $old['has_env']) {
                    putenv("{$key}=".($old['server'] ?? $old['env'] ?? ''));
                } else {
                    putenv($key);
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Production config sanity
    // ------------------------------------------------------------------

    public function test_debug_does_not_default_to_true(): void
    {
        // config/app.php must keep APP_DEBUG defaulting to false so a
        // missing env var can never expose stack traces in production.
        $backup = [
            'server' => $_SERVER['APP_DEBUG'] ?? null,
            'env' => $_ENV['APP_DEBUG'] ?? null,
            'has_server' => array_key_exists('APP_DEBUG', $_SERVER),
            'has_env' => array_key_exists('APP_DEBUG', $_ENV),
        ];

        try {
            unset($_SERVER['APP_DEBUG'], $_ENV['APP_DEBUG']);
            putenv('APP_DEBUG');

            $config = require config_path('app.php');

            $this->assertFalse(
                $config['debug'],
                'APP_DEBUG must default to false when the env var is absent.'
            );
        } finally {
            if ($backup['has_server']) {
                $_SERVER['APP_DEBUG'] = $backup['server'];
            } else {
                unset($_SERVER['APP_DEBUG']);
            }
            if ($backup['has_env']) {
                $_ENV['APP_DEBUG'] = $backup['env'];
            } else {
                unset($_ENV['APP_DEBUG']);
            }
            if ($backup['has_server'] || $backup['has_env']) {
                putenv('APP_DEBUG='.($backup['server'] ?? $backup['env'] ?? ''));
            } else {
                putenv('APP_DEBUG');
            }
        }
    }
}
