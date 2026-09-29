<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Like Laravel's "verified" middleware, but honours the
 * Settings Center 'Email verification required' switch (falls back to EMAIL_VERIFICATION_ENABLED) — when disabled, everyone passes.
 */
class EnsureEmailVerifiedIfEnabled
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        if (! settings_bool('features.email_verification', true)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail())) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : redirect()->guest(route('verification.notice'));
        }

        return $next($request);
    }
}
