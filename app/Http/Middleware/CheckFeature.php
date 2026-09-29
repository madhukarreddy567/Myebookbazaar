<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Feature-switch gate for public routes.
 * Usage: ->middleware('feature:features.registration')
 *
 * When the switch is OFF the route is blocked with a friendly message
 * (403 page for browsers, JSON for API-style requests) instead of
 * silently failing.
 */
class CheckFeature
{
    public function handle(Request $request, Closure $next, string $settingKey): Response
    {
        if (settings_enabled($settingKey, true)) {
            return $next($request);
        }

        $label = $this->label($settingKey);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $label.' is currently disabled. Please try again later.',
            ], 403);
        }

        return response()->view('pages.feature-disabled', [
            'feature' => $label,
        ], 403);
    }

    protected function label(string $settingKey): string
    {
        $name = (string) str($settingKey)->afterLast('.')->replace('_', ' ');

        return ucfirst($name);
    }
}
