<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\VerifiesTurnstile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Support\Security;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    use VerifiesTurnstile;

    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Admin login. Rate limited per IP+email (limit from Settings),
     * then an extended lockout. Phase 6: Turnstile + TOTP 2FA challenge.
     */
    public function login(AdminLoginRequest $request): RedirectResponse
    {
        // Phase 6: bot check before the credential attempt.
        $this->verifyTurnstile($request, 'admin_login');

        $key = $request->throttleKey();
        $lockoutKey = 'admin-login-lockout:'.$key;

        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($lockoutKey).' seconds.',
            ]);
        }

        $limit = max(1, (int) settings('security.rate_limit_login', 5));

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $minutes = max(1, (int) settings('security.lockout_minutes', 15));
            RateLimiter::hit($lockoutKey, $minutes * 60);
            event(new Lockout($request));
            Security::log('login.lockout', 'Admin login locked out after repeated failures.', [
                'email' => strtolower((string) $request->string('email')),
                'lockout_minutes' => $minutes,
            ]);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in ".($minutes * 60).' seconds.',
            ]);
        }

        $credentials = $request->only('email', 'password');

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            Security::log('login.failed', 'Admin login failed.', [
                'email' => strtolower((string) $request->string('email')),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        // Phase 5: disabled admin accounts cannot log in. The failure
        // response is identical to a wrong password so disabled status
        // cannot be enumerated; the real reason is in the security log.
        if (! $admin->is_active) {
            Auth::guard('admin')->logout();
            RateLimiter::hit($key, 60);
            Security::log('login.failed', 'Disabled admin account attempted login.', [
                'email' => strtolower((string) $request->string('email')),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        RateLimiter::clear($lockoutKey);

        // Phase 6: TOTP 2FA challenge when the admin enabled it.
        if ($admin->hasTwoFactorEnabled()) {
            $request->session()->put('two_factor_admin_id', $admin->id);
            $request->session()->put('two_factor_remember', $request->boolean('remember'));
            Auth::guard('admin')->logout();

            return redirect()->route('reddy.2fa.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('reddy.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('reddy.login');
    }
}
