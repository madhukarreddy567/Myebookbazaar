<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use App\Services\GoogleOAuthService;
use App\Support\Security;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Customer sign-in / sign-up with Google (OAuth 2.0).
 * Credentials come from Settings (auth.google.*); email/password
 * authentication is untouched and keeps working as before.
 */
class GoogleAuthController extends Controller
{
    /**
     * Send the visitor to Google's consent screen.
     */
    public function redirect(GoogleOAuthService $google): RedirectResponse
    {
        if (! $google->configured()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in is not available right now. Please log in with your email.']);
        }

        $google->applyConfig();

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google's callback: find-or-create the customer, link
     * google_id, merge the guest cart, and log them in.
     */
    public function callback(Request $request, GoogleOAuthService $google): RedirectResponse
    {
        // The visitor clicked "Cancel"/denied consent on Google's screen.
        if ($request->filled('error')) {
            return redirect()->route('login')
                ->with('status', 'Google sign-in was cancelled. You can log in with your email instead.');
        }

        if (! $google->configured()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in is not available right now. Please log in with your email.']);
        }

        $google->applyConfig();

        try {
            $social = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);
            Security::log('google.callback_failed', 'Google OAuth callback failed.', []);

            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again or log in with your email.']);
        }

        $email = strtolower(trim((string) $social->getEmail()));
        if ($email === '') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google did not share an email address. Please log in with your email.']);
        }

        $emailVerified = (bool) ($social->user['email_verified'] ?? false);

        // Capture the guest session id BEFORE login regenerates it.
        $guestSessionId = $request->session()->getId();

        $user = User::where('google_id', $social->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // Only link a Google identity to an existing account when
                // Google confirms the email belongs to this person —
                // otherwise anyone could claim someone else's account.
                if (! $emailVerified) {
                    return redirect()->route('login')
                        ->withErrors(['email' => 'An account with this email already exists. Please log in with your password first.']);
                }
                $user->google_id = $social->getId();
                $user->save();
            } else {
                $user = User::create([
                    'name' => $social->getName() ?: $email,
                    'email' => $email,
                    // Unusable random password — the account is accessed
                    // via Google; "forgot password" can set a real one.
                    'password' => Str::random(64),
                    'google_id' => $social->getId(),
                    'country_code' => null,
                    'country_name' => null,
                    'pricing_region' => 'INTL',
                    'email_verified_at' => $emailVerified ? now() : null,
                ]);
                event(new Registered($user));
            }
        }

        // Blocked users get the same generic failure as password login.
        if ($user->is_blocked) {
            Auth::logout();
            Security::log('google.blocked_login', 'Blocked user attempted Google sign-in.', ['user_id' => $user->id]);

            return redirect()->route('login')
                ->withErrors(['email' => __('auth.failed')]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        Cart::mergeGuestToUser($guestSessionId, $user);

        // Google-verified emails skip the verification notice, exactly
        // like a user who just verified via the emailed link.
        if (settings_bool('features.email_verification', true) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('account'));
    }
}
