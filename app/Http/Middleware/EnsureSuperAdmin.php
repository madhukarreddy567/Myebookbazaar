<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Super Admin only. Used for the Settings Center, updates, backups and
 * anything that touches secrets. Staff admins get a 403.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ($admin->role ?? 'super_admin') !== 'super_admin') {
            abort(403, 'Only a Super Admin can access this area.');
        }

        return $next($request);
    }
}
