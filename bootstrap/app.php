<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\CheckFeature;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureEmailVerifiedIfEnabled;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTwoFactorForSuperAdmin;
use App\Http\Middleware\RequireCustomerAuth;
use App\Http\Middleware\RequireGuestBrowsing;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trusted proxies (Cloudflare): set TRUSTED_PROXIES=* in .env when
        // the site sits behind Cloudflare so request->ip() returns the real
        // visitor IP instead of Cloudflare's. Leave empty when NOT behind a
        // proxy. Supports a comma-separated list of IPs/CIDRs or "*".
        $trusted = trim((string) env('TRUSTED_PROXIES', ''));
        if ($trusted !== '') {
            $proxies = $trusted === '*' ? '*' : array_map('trim', explode(',', $trusted));
            $middleware->trustProxies(at: $proxies);
        }

        // Security headers on every response.
        $middleware->append(SecurityHeaders::class);

        // Phase 6: settings-driven maintenance mode (public routes only;
        // /reddy and /webhooks/* always stay reachable).
        $middleware->append(CheckMaintenanceMode::class);

        // Reusable aliases.
        $middleware->alias([
            // Customer "auth required" for current + future (Buy/Checkout) actions.
            'customer' => RequireCustomerAuth::class,
            // Admin-area guard (separate session from customers).
            'admin' => AdminAuthenticate::class,
            // Email verification that honours the EMAIL_VERIFICATION_ENABLED switch.
            'verified.if.enabled' => EnsureEmailVerifiedIfEnabled::class,
            // Super Admin only (Settings Center, updates, backups, secrets).
            'super_admin' => EnsureSuperAdmin::class,
            // Phase 6: force Super Admins through 2FA setup when required.
            'require_2fa' => EnsureTwoFactorForSuperAdmin::class,
            // Feature-switch gate: ->middleware('feature:features.registration')
            'feature' => CheckFeature::class,
            // Guest-browsing switch for store routes (logged-in users bypass).
            'guest.browsing' => RequireGuestBrowsing::class,
        ]);

        // Guests of the admin area are redirected to /reddy/login.
        $middleware->redirectGuestsTo(function ($request) {
            return $request->is('reddy*')
                ? route('reddy.login')
                : route('login');
        });

        // Razorpay calls the webhook server-to-server: no session, no CSRF
        // token. Authenticity is proven by the HMAC webhook signature.
        $middleware->validateCsrfTokens(except: [
            'webhooks/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
