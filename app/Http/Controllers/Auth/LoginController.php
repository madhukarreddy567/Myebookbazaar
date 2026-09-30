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
        // Sidebar spotlight card: the real featured book (if any), priced
        // for the visitor. Never invents data.
        $spotlight = \App\Models\Book::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();
        if ($spotlight) {
            $prices = app(\App\Services\PriceService::class);
            $quote = $prices->getPrice($spotlight);
            $spotlight->price_display = $prices->format($quote['amount'], $quote['currency']);
            $spotlight->price_regular_display = $quote['regular_amount'] !== null
                ? $prices->format($quote['regular_amount'], $quote['currency'])
                : null;
        }

        return view('auth.login', compact('spotlight'));
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
