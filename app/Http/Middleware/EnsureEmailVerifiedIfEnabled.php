<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Like Laravel's "verified" middleware, but honours the
 * EMAIL_VERIFICATION_ENABLED switch — when disabled, everyone passes.
 */
class EnsureEmailVerifiedIfEnabled
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        if (! config('app.email_verification_enabled')) {
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
