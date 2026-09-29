<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Support\Security;

class LoginRequest extends FormRequest
{
    /**
     * Syntactically valid bcrypt hash used to burn a hash check for
     * unknown emails (no user enumeration via timing).
     */
    protected const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // No user enumeration: an unknown email costs a hash check too,
            // so timing does not reveal whether the account exists.
            if (! User::where('email', $this->string('email'))->exists()) {
                Hash::check((string) $this->string('password'), self::DUMMY_HASH);
            }

            // Phase 6: failed logins go to the security log (never the password).
            Security::log('login.failed', 'Customer login failed.', [
                'email' => strtolower((string) $this->string('email')),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Blocked users can never log in. The failure response is identical
        // to a wrong password so blocked status cannot be enumerated.
        if ($user->is_blocked) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Phase 6: two-tier login protection from Settings.
     * Tier 1: security.rate_limit_login attempts per 60s per email+IP.
     * Tier 2: tripping tier 1 engages an extended lockout of
     * security.lockout_minutes minutes (logged once, not per request).
     */
    public function ensureIsNotRateLimited(): void
    {
        $lockoutKey = 'login-lockout:'.$this->throttleKey();

        // Already inside an extended lockout: refuse quietly (no log spam).
        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($lockoutKey),
                ]),
            ]);
        }

        $limit = max(1, (int) settings('security.rate_limit_login', 5));

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $limit)) {
            return;
        }

        $minutes = max(1, (int) settings('security.lockout_minutes', 15));
        RateLimiter::hit($lockoutKey, $minutes * 60);

        event(new Lockout($this));

        Security::log('login.lockout', 'Customer login locked out after repeated failures.', [
            'email' => strtolower((string) $this->string('email')),
            'lockout_minutes' => $minutes,
        ]);

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                'seconds' => $minutes * 60,
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
