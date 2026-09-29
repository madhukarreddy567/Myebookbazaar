<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline security headers to every response.
 * The CSP allow-list covers Tailwind, Alpine.js, Google Fonts,
 * Cloudflare Turnstile and Razorpay (needed in later phases).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://challenges.cloudflare.com https://js.razorpay.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data:",
            "frame-src https://www.google.com https://challenges.cloudflare.com https://api.razorpay.com",
            "connect-src 'self' https://api.razorpay.com https://challenges.cloudflare.com",
            "object-src 'none'",
            "base-uri 'self'",
        ]);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', $csp);

        // Remove fingerprinting headers where possible.
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
