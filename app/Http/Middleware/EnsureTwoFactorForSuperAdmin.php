<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 6: enforce `security.require_2fa_admin`.
 *
 * When the switch is ON, a Super Admin who has not enabled TOTP 2FA is
 * redirected to the 2FA setup page on every admin request (except the
 * 2FA pages themselves and logout). Staff accounts are unaffected.
 */
class EnsureTwoFactorForSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if (
            $admin
            && $admin->isSuperAdmin()
            && ! $admin->hasTwoFactorEnabled()
            && settings_bool('security.require_2fa_admin', false)
            && ! $request->routeIs('reddy.2fa.*')
            && ! $request->routeIs('reddy.account.security*')
            && ! $request->routeIs('reddy.logout')
        ) {
            return redirect()->route('reddy.2fa.setup')
                ->with('status', 'Two-factor authentication is required for Super Admins. Please set it up to continue.');
        }

        return $next($request);
    }
}
