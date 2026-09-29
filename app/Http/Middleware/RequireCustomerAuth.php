<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reusable "auth required" middleware for customer-only actions
 * (Buy / Checkout arrive in later phases).
 *
 * Guests are redirected to the login page and returned to their
 * original destination afterwards (intended URL).
 */
class RequireCustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->guest()) {
            return redirect()->guest(route('login'));
        }

        // Belt and braces: a blocked user can never proceed.
        if (Auth::guard('web')->user()->is_blocked) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')
                ->withErrors(['email' => 'This account has been blocked. Please contact support.']);
        }

        return $next($request);
    }
}
