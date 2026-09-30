<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Mail\SettingsTestMail;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\Setting;
use App\Services\RemoveBgService;
use App\Services\SettingsService;
use App\Settings\Definition;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Settings Center (Phase 2A) — Super Admin only.
 * Every key, credential, switch, limit and text lives here; only APP_KEY
 * and the database credentials stay in .env.
 */
class SettingsController extends Controller
{
    public function index(SettingsService $settings)
    {
        $tab = request('tab', 'general');
        if (! array_key_exists($tab, Definition::tabs())) {
            $tab = 'general';
        }

        $rows = Setting::query()->get()->keyBy('key');

        $fieldViews = [];
        foreach (Definition::fields($tab) as $field) {
            $key = $field['key'];
            $row = $rows->get($key);
            $isSecret = $field['type'] === 'secret' || (bool) ($row->is_secret ?? false);

            $fieldViews[] = [
                'field' => $field,
                // Secrets are NEVER sent back to the browser.
                'value' => $isSecret ? null : $settings->get($key),
                'masked' => $isSecret ? $settings->masked($key) : null,
                'has_value' => $isSecret
                    ? $settings->masked($key) !== null
                    : ($settings->get($key) !== null && $settings->get($key) !== ''),
            ];
        }

        $countries = [];
        $regionOverrides = [];
        if ($tab === 'pricing') {
            $raw = $settings->get('pricing.country_regions');
            $regionOverrides = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
            $countries = Country::orderBy('name')->get(['iso_code', 'name'])->map(function ($c) use ($regionOverrides) {
                $iso = $c->iso_code;

                return [
                    'iso' => $iso,
                    'name' => $c->name,
                    'region' => $regionOverrides[$iso] ?? ($iso === 'IN' ? 'IN' : 'INTL'),
                ];
            });
        }

        return view('admin.settings.index', [
            'tabs' => Definition::tabs(),
            'tab' => $tab,
            'fieldViews' => $fieldViews,
            'countries' => $countries,
            'health' => $this->health($settings),
            'webhookUrl' => url('/webhooks/razorpay'),
            'googleRedirectUrl' => route('auth.google.callback'),
            'recentChanges' => AuditLog::with('admin:id,name')
                ->where('action', 'settings.updated')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingsService $settings, RemoveBgService $removeBg)
    {
        $tab = $request->input('tab');
        $adminId = auth('admin')->id();
        $fields = Definition::fields($tab);
        $fieldKeys = collect($fields)->pluck('key')->all();
        $flat = $request->settingValues();

        $oldValues = [];
        $newValues = [];

        $save = function (string $key, mixed $new, bool $isSecret = false) use ($settings, $adminId, &$oldValues, &$newValues) {
            $hadValue = $settings->has($key) && $settings->get($key) !== null && $settings->get($key) !== '';
            $oldRaw = $isSecret ? null : $settings->get($key);
            $newRaw = $new === '' ? null : $new;

            if ((string) $oldRaw === (string) $newRaw && ! $isSecret) {
                return;
            }

            // For secrets we never read the old plaintext for comparison;
            // any submitted value counts as a change.
            $settings->set($key, $newRaw, $adminId);

            if ($isSecret) {
                $oldValues[$key] = $hadValue ? '***redacted***' : null;
                $newValues[$key] = $newRaw === null ? '***cleared***' : '***changed***';
            } else {
                $oldValues[$key] = $oldRaw;
                $newValues[$key] = $newRaw;
            }
        };

        foreach ($fields as $field) {
            $key = $field['key'];
            if (! in_array($key, $fieldKeys, true)) {
                continue;
            }

            switch ($field['type']) {
                case 'boolean':
                    // Unchecked boxes are absent from the request => OFF.
                    $save($key, $request->boolean('s.'.$key) ? '1' : '0');
                    break;

                case 'secret':
                    // Only a typed-in value changes a secret; the masked
                    // display value is never submitted.
                    $secretInput = $request->input('secret_'.str_replace('.', '_', $key));
                    if ($secretInput !== null && $secretInput !== '') {
                        $save($key, $secretInput, true);
                    }
                    break;

                case 'image':
                    $this->handleImage($request, $settings, $removeBg, $key, $adminId, $oldValues, $newValues);
                    break;

                default:
                    if (array_key_exists($key, $flat)) {
                        $save($key, $flat[$key]);
                    }
                    break;
            }
        }

        // Country region overrides (pricing tab): only non-default entries
        // are stored, as JSON in pricing.country_regions.
        if ($tab === 'pricing') {
            $map = [];
            foreach ($request->input('regions', []) as $iso => $region) {
                $iso = strtoupper((string) $iso);
                if (! in_array($region, ['IN', 'INTL'], true)) {
                    continue;
                }
                $default = $iso === 'IN' ? 'IN' : 'INTL';
                if ($region !== $default) {
                    $map[$iso] = $region;
                }
            }
            ksort($map);
            $save('pricing.country_regions', $map === [] ? null : json_encode($map));
        }

        if ($oldValues !== [] || $newValues !== []) {
            Audit::record('settings.updated', 'settings', null, $oldValues, $newValues, $adminId);
        }

        return redirect()
            ->route('reddy.settings', ['tab' => $tab])
            ->with('status', Definition::tabLabel($tab).' settings saved.');
    }

    /**
     * Send a real test email using the SMTP settings, and report success
     * or the exact error as JSON.
     */
    public function testEmail(Request $request, SettingsService $settings)
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        $to = $request->input('email');

        try {
            $encryption = $settings->get('mail.encryption');
            config(['mail.mailers.settings-test' => [
                'transport' => 'smtp',
                'host' => $settings->get('mail.host'),
                'port' => (int) $settings->get('mail.port', 587),
                'encryption' => $encryption === 'none' ? null : $encryption,
                'username' => $settings->get('mail.username'),
                'password' => $settings->get('mail.password'),
                'timeout' => 10,
            ]]);

            Mail::mailer('settings-test')
                ->to($to)
                ->send((new SettingsTestMail())->from(
                    $settings->get('mail.from_address') ?: 'hello@myebookbazaar.com',
                    $settings->get('mail.from_name') ?: 'MyEbookBazaar'
                ));

            Audit::record('settings.test_email', 'settings', null, [], [
                'to' => $to,
                'result' => 'sent',
            ]);

            return response()->json(['ok' => true, 'message' => "Test email sent to {$to}."]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                // The message describes the failure; credentials are never included.
                'message' => 'Could not send the test email: '.mb_substr($e->getMessage(), 0, 500),
            ], 422);
        }
    }

    /**
     * Razorpay "Test connection". The real API check arrives with the
     * Phase 3 checkout; until then this validates that the keys are
     * present and match the selected mode, without revealing them.
     */
    public function testRazorpay(SettingsService $settings, \App\Services\RazorpayService $razorpay)
    {
        // Phase 3: real lightweight API check against Razorpay.
        $result = $razorpay->testConnection();

        Audit::record('settings.test_razorpay', 'settings', null, [], [
            'mode' => $settings->get('payments.razorpay_mode', 'test'),
            'result' => $result['ok'] ? 'connected' : 'failed',
        ]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Phase 6: Test Connection for Cloudflare Turnstile — validates the
     * secret key against Cloudflare's siteverify endpoint.
     */
    public function testTurnstile(SettingsService $settings, \App\Services\TurnstileService $turnstile)
    {
        $result = $turnstile->testConnection();

        Audit::record('settings.test_turnstile', 'settings', null, [], [
            'result' => $result['ok'] ? 'connected' : 'failed',
        ]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Google OAuth "Test connection" — validates the Client ID and Secret
     * against Google's token endpoint. The secret is only ever sent to
     * Google over HTTPS; it never appears in the response, logs, or audit.
     */
    public function testGoogle(SettingsService $settings, \App\Services\GoogleOAuthService $google)
    {
        $result = $google->testConnection();

        Audit::record('settings.test_google', 'settings', null, [], [
            'result' => $result['ok'] ? 'connected' : 'failed',
        ]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    // ------------------------------------------------------------------

    protected function handleImage(UpdateSettingsRequest $request, SettingsService $settings, RemoveBgService $removeBg, string $key, ?int $adminId, array &$oldValues, array &$newValues): void
    {
        $flat = str_replace('.', '_', $key);

        // Removal requested.
        if ($request->boolean('remove_img_'.$flat)) {
            $this->deleteStoredImage($settings->get($key));
            $hadValue = $settings->get($key) !== null && $settings->get($key) !== '';
            $settings->set($key, null, $adminId);
            if ($hadValue) {
                $oldValues[$key] = '***image removed***';
                $newValues[$key] = null;
            }

            return;
        }

        $file = $request->file('img_'.$flat);
        if (! $file) {
            return;
        }

        $dir = public_path('uploads/settings');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = Str::random(32).'.'.$file->getClientOriginalExtension();
        $file->move($dir, $name);

        $storedRelative = 'uploads/settings/'.$name;

        // Optional remove.bg background removal: only for the site logo,
        // only when an API key is configured. On API failure the original
        // upload is kept and a warning is flashed — the upload never breaks.
        $bgStatus = null;
        if ($key === 'site.logo' && $removeBg->enabled()) {
            $png = $removeBg->removeBackground($dir.'/'.$name);
            if ($png === null) {
                $bgStatus = 'failed';
                session()->flash(
                    'warning',
                    'Logo saved, but background removal failed (remove.bg did not return an image). The original upload was kept.'
                );
            } else {
                // The API returns a transparent PNG — store it under a .png
                // name so the extension always matches the bytes. The
                // original transparency is preserved (never flattened).
                $pngName = pathinfo($name, PATHINFO_FILENAME).'.png';
                file_put_contents($dir.'/'.$pngName, $png);
                if ($pngName !== $name) {
                    @unlink($dir.'/'.$name);
                }
                $storedRelative = 'uploads/settings/'.$pngName;
                $bgStatus = 'applied';
            }
        }

        $this->deleteStoredImage($settings->get($key));

        $old = $settings->get($key);
        $settings->set($key, $storedRelative, $adminId);
        $oldValues[$key] = $old;
        $newValues[$key] = $storedRelative;

        // Audit the background-removal outcome (never the API key).
        if ($bgStatus !== null) {
            Audit::record('settings.logo_background', 'settings', null, [], [
                'result' => $bgStatus,
                'file' => $storedRelative,
            ], $adminId);
        }
    }

    protected function deleteStoredImage(mixed $path): void
    {
        if (is_string($path) && str_starts_with($path, 'uploads/settings/')) {
            $full = public_path($path);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    /**
     * "Settings health": what is not configured yet.
     */
    protected function health(SettingsService $settings): array
    {
        $items = [];
        $add = function (string $level, string $message, string $tab) use (&$items) {
            $items[] = compact('level', 'message', 'tab');
        };

        if (! $settings->get('site.name')) {
            $add('warning', 'Site name is not set.', 'general');
        }
        if (! $settings->get('site.support_email')) {
            $add('warning', 'Support email is not set.', 'general');
        }

        if ($settings->enabled('payments.razorpay_enabled')) {
            if (! $settings->get('payments.razorpay_key_id')) {
                $add('warning', 'Razorpay is enabled but the Key ID is missing.', 'payments');
            }
            if (! $settings->masked('payments.razorpay_key_secret')) {
                $add('warning', 'Razorpay is enabled but the Key Secret is missing.', 'payments');
            }
            if (! $settings->masked('payments.razorpay_webhook_secret')) {
                $add('info', 'No Razorpay webhook secret set — webhooks cannot be verified yet.', 'payments');
            }
        } else {
            $add('info', 'Razorpay payments are disabled.', 'payments');
        }

        if (! $settings->get('mail.host')) {
            $add('warning', 'SMTP is not configured — no emails can be sent.', 'mail');
        }

        if ($settings->enabled('security.turnstile_enabled')) {
            if (! $settings->get('security.turnstile_site_key')) {
                $add('warning', 'Turnstile is enabled but the site key is missing.', 'security');
            }
            if (! $settings->masked('security.turnstile_secret_key')) {
                $add('warning', 'Turnstile is enabled but the secret key is missing.', 'security');
            }
        }

        if (! $settings->get('legal.privacy_policy')) {
            $add('info', 'Privacy policy text is empty.', 'legal');
        }
        if (! $settings->get('legal.terms')) {
            $add('info', 'Terms of service text is empty.', 'legal');
        }

        return $items;
    }
}
