<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Show the "verify your email" notice. Disabled entirely when the
     * Settings Center 'Email verification required' switch (falls back to EMAIL_VERIFICATION_ENABLED) is off.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        if (! settings_bool('features.email_verification', true)) {
            return redirect()->route('home');
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('account'));
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! settings_bool('features.email_verification', true)) {
            return redirect()->route('home');
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('account'));
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($request->user()));
        }

        return redirect()->intended(route('account'))->with('status', 'Your email has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if (! settings_bool('features.email_verification', true)) {
            return redirect()->route('home');
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('account'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A fresh verification link has been sent to your email.');
    }
}
