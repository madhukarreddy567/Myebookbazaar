<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 2B: honours the "Guest browsing" switch for visitors who are not
 * logged in. When OFF, guests see the friendly "currently unavailable"
 * page on store routes; logged-in customers are never blocked.
 *
 * Usage: ->middleware('guest.browsing')
 */
class RequireGuestBrowsing
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() && ! settings_enabled('features.guest_browsing', true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Browsing is currently disabled. Please try again later.',
                ], 403);
            }

            return response()->view('pages.feature-disabled', [
                'feature' => 'Browsing',
            ], 403);
        }

        return $next($request);
    }
}
