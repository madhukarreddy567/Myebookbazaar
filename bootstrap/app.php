<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnsureEmailVerifiedIfEnabled;
use App\Http\Middleware\RequireCustomerAuth;
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
        // Security headers on every response.
        $middleware->append(SecurityHeaders::class);

        // Reusable aliases.
        $middleware->alias([
            // Customer "auth required" for current + future (Buy/Checkout) actions.
            'customer' => RequireCustomerAuth::class,
            // Admin-area guard (separate session from customers).
            'admin' => AdminAuthenticate::class,
            // Email verification that honours the EMAIL_VERIFICATION_ENABLED switch.
            'verified.if.enabled' => EnsureEmailVerifiedIfEnabled::class,
        ]);

        // Guests of the admin area are redirected to /reddy/login.
        $middleware->redirectGuestsTo(function ($request) {
            return $request->is('reddy*')
                ? route('reddy.login')
                : route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
