<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-area guard. Customers (web guard) can never pass this —
 * only the separate "admin" guard session is accepted.
 */
class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->guest()) {
            return redirect()->guest(route('reddy.login'));
        }

        return $next($request);
    }
}
