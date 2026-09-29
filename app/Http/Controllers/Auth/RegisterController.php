<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\VerifiesTurnstile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\HoneypotService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\Security;

class RegisterController extends Controller
{
    use VerifiesTurnstile;

    public function show(): View
    {
        $countries = Country::orderBy('name')->get(['iso_code', 'name', 'phone_code']);

        return view('auth.register', compact('countries'));
    }

    public function store(RegisterRequest $request, HoneypotService $honeypot): RedirectResponse
    {
        // Phase 6: per-IP registration rate limit from Settings.
        $this->ensureRegisterNotRateLimited($request);

        // Phase 6: bots are silently discarded with a generic success.
        if ($honeypot->isBot($request)) {
            $honeypot->recordHit($request, 'register');

            return redirect()->route('login')
                ->with('status', 'Account created — please check your email to verify it.');
        }

        $this->verifyTurnstile($request, 'register');
        $country = Country::where('iso_code', $request->validated()['country_code'])->firstOrFail();

        $user = User::create([
            'name' => $request->validated()['name'],
            'email' => $request->validated()['email'],
            'password' => $request->validated()['password'], // hashed via model cast
            'country_code' => $country->iso_code,
            'country_name' => $country->name,
            // Pricing region is derived once at signup (with the admin's
            // per-country overrides from the Settings Center) and never
            // changes afterwards.
            'pricing_region' => app(\App\Services\SettingsService::class)->regionForCountry($country->iso_code),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        if (settings_bool('features.email_verification', true)) {
            event(new Registered($user));

            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('account'));
    }

    /**
     * Phase 6: registration rate limit from Settings
     * (security.rate_limit_register, per IP).
     */
    protected function ensureRegisterNotRateLimited(RegisterRequest $request): void
    {
        $limit = max(1, (int) settings('security.rate_limit_register', 5));
        $key = 'register|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            Security::log('register.lockout', 'Registration rate limit hit.', ['ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);
    }
}
