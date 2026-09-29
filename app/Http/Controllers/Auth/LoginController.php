<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\VerifiesTurnstile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    use VerifiesTurnstile;

    public function show(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Phase 6: bot check before the credential attempt.
        $this->verifyTurnstile($request, 'login');

        // Capture the guest session id BEFORE it is regenerated, so the
        // guest cart can be merged onto the user after login (Phase 2B).
        $guestSessionId = $request->session()->getId();

        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        Cart::mergeGuestToUser($guestSessionId, $user);

        // If email verification is enabled, unverified users must verify first.
        if (settings_bool('features.email_verification', true) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
