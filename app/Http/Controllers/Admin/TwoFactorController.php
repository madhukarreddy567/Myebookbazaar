<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\TotpService;
use App\Support\Security;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Phase 6: TOTP two-factor authentication for admins.
 *
 * Three flows live here:
 *  1. Login challenge — after a correct password, an admin with 2FA
 *     enabled must enter a 6-digit code (or a recovery code) before the
 *     real admin session starts. Reached while logged OUT; the pending
 *     admin id sits in the session.
 *  2. Setup — from /reddy/account/security: show QR + manual secret,
 *     confirm with a code, then show the 10 recovery codes ONCE.
 *  3. Disable — requires the current password; wipes secret + codes.
 */
class TwoFactorController extends Controller
{
    public function __construct(protected TotpService $totp) {}

    // ------------------------------------------------------------------
    // 1. Login challenge (guest:admin context, pending 2FA session).
    // ------------------------------------------------------------------

    public function showChallenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor_admin_id')) {
            return redirect()->route('reddy.login');
        }

        return view('admin.auth.2fa-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $adminId = $request->session()->get('two_factor_admin_id');

        if (! $adminId) {
            return redirect()->route('reddy.login');
        }

        $request->validate(['code' => ['required', 'string', 'max:32']]);

        /** @var Admin|null $admin */
        $admin = Admin::find($adminId);

        if (! $admin || ! $admin->is_active || ! $admin->hasTwoFactorEnabled()) {
            $request->session()->forget(['two_factor_admin_id', 'two_factor_remember']);

            return redirect()->route('reddy.login');
        }

        $code = preg_replace('/\s+/', '', (string) $request->input('code'));

        // Recovery codes: single-use, matched by hash.
        $hashes = $admin->two_factor_recovery_codes ?? [];
        $matched = $this->totp->matchRecoveryCode($hashes, $code);

        if ($matched !== null) {
            unset($hashes[$matched]);
            $admin->forceFill(['two_factor_recovery_codes' => array_values($hashes)])->save();
            Security::log('2fa.recovery_used', 'Admin signed in with a 2FA recovery code.', ['admin_id' => $admin->id]);

            return $this->finishChallenge($request, $admin);
        }

        if (! $this->totp->verify($admin->two_factor_secret, $code)) {
            Security::log('2fa.challenge_failed', 'Admin 2FA challenge failed.', ['admin_id' => $admin->id]);

            throw ValidationException::withMessages([
                'code' => 'That code did not work. Try again.',
            ]);
        }

        return $this->finishChallenge($request, $admin);
    }

    protected function finishChallenge(Request $request, Admin $admin): RedirectResponse
    {
        Auth::guard('admin')->login($admin, (bool) $request->session()->pull('two_factor_remember', false));
        $request->session()->forget('two_factor_admin_id');
        $request->session()->regenerate();

        Security::log('login.success', 'Admin signed in (2FA verified).', ['admin_id' => $admin->id]);

        return redirect()->intended(route('reddy.dashboard'));
    }

    // ------------------------------------------------------------------
    // 2. Setup (authenticated admin).
    // ------------------------------------------------------------------

    public function showSetup(Request $request): View|RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if ($admin->hasTwoFactorEnabled()) {
            return redirect()->route('reddy.account.security');
        }

        // A fresh secret per setup attempt, kept server-side until confirmed.
        $secret = $this->totp->generateSecret();
        $request->session()->put('two_factor_setup_secret', $secret);

        return view('admin.account.2fa-setup', [
            'qrSvg' => $this->totp->qrCodeSvg($this->totp->provisioningUri($secret, $admin->email)),
            'secret' => $secret,
        ]);
    }

    public function confirmSetup(Request $request): View|RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $secret = $request->session()->get('two_factor_setup_secret');

        if ($admin->hasTwoFactorEnabled() || ! $secret) {
            return redirect()->route('reddy.account.security');
        }

        $request->validate(['code' => ['required', 'string', 'size:6']]);

        if (! $this->totp->verify($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'That code did not work. Scan the QR code again and try the current code.',
            ]);
        }

        $recovery = $this->totp->generateRecoveryCodes();

        $admin->forceFill([
            'two_factor_secret' => $secret, // encrypted by the model mutator
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => $recovery['hashed'],
        ])->save();

        $request->session()->forget('two_factor_setup_secret');

        Security::log('2fa.enabled', 'Admin enabled two-factor authentication.');

        // Recovery codes are shown ONCE, right here, then never again.
        return view('admin.account.2fa-recovery-codes', [
            'recoveryCodes' => $recovery['plain'],
        ]);
    }

    // ------------------------------------------------------------------
    // 3. Disable (authenticated admin, password required).
    // ------------------------------------------------------------------

    public function disable(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check((string) $request->input('password'), $admin->password)) {
            throw ValidationException::withMessages([
                'password' => 'Your password did not match.',
            ]);
        }

        $admin->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        Security::log('2fa.disabled', 'Admin disabled two-factor authentication.');

        return redirect()->route('reddy.account.security')
            ->with('status', 'Two-factor authentication has been turned off.');
    }

    // ------------------------------------------------------------------
    // Account security page.
    // ------------------------------------------------------------------

    public function showSecurity(Request $request): View
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $recentEvents = \App\Models\SecurityLog::forAdmin($admin->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.account.security', [
            'admin' => $admin,
            'recentEvents' => $recentEvents,
            'require2fa' => settings_bool('security.require_2fa_admin', false),
        ]);
    }
}
