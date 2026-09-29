{{--
  Settings Center (Phase 2A). Super Admin only.
  Tabs are server-side (?tab=...); each tab has its own form.
--}}
@extends('layouts.admin')

@section('title', 'Settings Center')
@section('page_title', 'Settings Center')
@section('breadcrumb')
System / Settings
@endsection

@section('content')
<x-admin.page-header title="Settings Center" subtitle="Every key, switch, limit and text for the store — nothing is hardcoded." />

{{-- Settings health: what is not configured yet --}}
@if(count($health))
<div class="surface p-5 mb-5">
    <h3 class="font-display text-lg font-bold text-[var(--color-text)] mb-3 flex items-center gap-2">
        <x-admin.icon name="alert" class="w-5 h-5 text-[var(--color-warning)]" />
        Settings health
    </h3>
    <ul class="space-y-2">
        @foreach($health as $item)
        <li class="flex items-start gap-2.5 text-sm">
            <x-admin.badge :status="$item['level'] === 'warning' ? 'pending' : 'draft'">{{ $item['level'] === 'warning' ? 'Needs attention' : 'Info' }}</x-admin.badge>
            <span class="text-[var(--color-text)] flex-1">{{ $item['message'] }}</span>
            <a href="{{ route('reddy.settings', ['tab' => $item['tab']]) }}"
               class="text-sm font-semibold text-[var(--color-teal-700)] underline underline-offset-2 shrink-0">Fix</a>
        </li>
        @endforeach
    </ul>
</div>
@endif

{{-- Tab navigation --}}
<div class="mb-5 flex gap-1 overflow-x-auto border-b border-[var(--color-border)]" role="tablist" aria-label="Settings sections">
    @foreach($tabs as $tabKey => $tabMeta)
    <a href="{{ route('reddy.settings', ['tab' => $tabKey]) }}" role="tab" aria-selected="{{ $tab === $tabKey ? 'true' : 'false' }}"
       class="shrink-0 px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px transition-colors
              {{ $tab === $tabKey
                  ? 'border-[var(--color-saffron-500)] text-[var(--color-text)]'
                  : 'border-transparent text-[var(--color-muted)] hover:text-[var(--color-text)]' }}">
        {{ $tabMeta['label'] }}
    </a>
    @endforeach
</div>

@if(false)
    {{-- Placeholder: Phase 7 fills this tab --}}
    <div class="surface p-8">
        <x-admin.empty-state title="Updates and integrations" icon="refresh"
            hint="Version-wise updates from GitHub arrive in Phase 7. This tab will hold the updater settings, the update history and the backup controls." />
    </div>
@else
    <div class="surface p-5 sm:p-7" x-data="settingsForm()">
        <form method="POST" action="{{ route('reddy.settings.update') }}" enctype="multipart/form-data"
              @input="dirty = true" @change="dirty = true" @submit="dirty = false" @reset="dirty = false">
            @csrf
            @method('PUT')
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="divide-y divide-[var(--color-border)]">
                @foreach($fieldViews as $fv)
                    @include('admin.settings.partials.field', ['fv' => $fv])
                @endforeach
            </div>

            {{-- Pricing tab extra: per-country region overrides --}}
            @if($tab === 'pricing')
                <div class="mt-6 border-t border-[var(--color-border)] pt-6">
                    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Country pricing regions</h3>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">
                        India defaults to <strong>IN</strong> (INR), every other country to <strong>INTL</strong> (USD).
                        Change any country below to override its default.
                    </p>
                    <div class="mt-4 max-h-[420px] overflow-y-auto rounded-xl border border-[var(--color-border)]">
                        <table class="data-table">
                            <thead class="sticky top-0 bg-[var(--color-surface)]">
                                <tr>
                                    <th class="text-left p-3">Country</th>
                                    <th class="text-left p-3">Code</th>
                                    <th class="text-left p-3">Region</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($countries as $c)
                                <tr class="border-t border-[var(--color-border)]">
                                    <td class="p-3">{{ $c['name'] }}</td>
                                    <td class="p-3 font-mono text-xs text-[var(--color-muted)]">{{ $c['iso'] }}</td>
                                    <td class="p-3">
                                        <select name="regions[{{ $c['iso'] }}]" class="input !min-h-[38px] !w-32 !py-1.5 !text-sm" aria-label="Pricing region for {{ $c['name'] }}">
                                            <option value="IN" @selected($c['region'] === 'IN')>IN (INR)</option>
                                            <option value="INTL" @selected($c['region'] === 'INTL')>INTL (USD)</option>
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Payments tab extra: webhook URL + test connection --}}
            @if($tab === 'payments')
                <div class="mt-6 border-t border-[var(--color-border)] pt-6" x-data="{ copied: false }">
                    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Razorpay webhook</h3>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Paste this URL into the Razorpay dashboard (webhooks). It becomes active with the Phase 3 checkout.</p>
                    <div class="mt-3 flex flex-col sm:flex-row gap-2">
                        <input type="text" readonly value="{{ $webhookUrl }}" id="webhook-url"
                               class="input font-mono !text-sm flex-1" onclick="this.select()">
                        <button type="button" class="btn btn-secondary shrink-0"
                                @click="navigator.clipboard.writeText('{{ $webhookUrl }}'); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-show="!copied">Copy URL</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </button>
                    </div>
                    <div class="mt-4" x-data="connectionTest('{{ route('reddy.settings.test-razorpay') }}')">
                        <button type="button" class="btn btn-secondary" @click="run()" :disabled="busy">
                            <span x-show="!busy">Test connection</span>
                            <span x-show="busy" x-cloak>Testing…</span>
                        </button>
                        <p x-show="message" x-cloak class="mt-2 text-sm"
                           :class="ok ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]'" x-text="message"></p>
                    </div>
                </div>
            @endif

            {{-- Email tab extra: send test email --}}
            @if($tab === 'mail')
                <div class="mt-6 border-t border-[var(--color-border)] pt-6" x-data="connectionTest('{{ route('reddy.settings.test-email') }}', true)">
                    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Send a test email</h3>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Sends using the SMTP settings above and reports success or the exact error.</p>
                    <div class="mt-3 flex flex-col sm:flex-row gap-2 max-w-xl">
                        <input type="email" x-model="email" required placeholder="you@example.com" class="input flex-1" aria-label="Test email recipient">
                        <button type="button" class="btn btn-secondary shrink-0" @click="run()" :disabled="busy">
                            <span x-show="!busy">Send test email</span>
                            <span x-show="busy" x-cloak>Sending…</span>
                        </button>
                    </div>
                    <p x-show="message" x-cloak class="mt-2 text-sm"
                       :class="ok ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]'" x-text="message"></p>
                </div>
            @endif

            @if($tab === 'updates')
                <div class="mt-6 border-t border-[var(--color-border)] pt-6" x-data="connectionTest('{{ route('reddy.settings.test-github') }}')">
                    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Test GitHub connection</h3>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Verifies your token can read the configured repository. Make sure you've saved the owner, name and token first.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="btn btn-secondary" @click="run()" :disabled="busy">
                            <span x-show="!busy">Test connection</span>
                            <span x-show="busy" x-cloak>Testing…</span>
                        </button>
                        <a href="{{ route('reddy.updates.index') }}" class="btn btn-ghost">Open updater</a>
                    </div>
                    <p x-show="message" x-cloak class="mt-2 text-sm"
                       :class="ok ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]'" x-text="message"></p>
                </div>

                <div class="mt-6 border-t border-[var(--color-border)] pt-6" x-data="connectionTest('{{ route('reddy.settings.test-google') }}')">
                    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Google sign-in setup</h3>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Create an OAuth 2.0 Client ID (Web application) in Google Cloud Console → APIs &amp; Services → Credentials, and add this exact Authorized redirect URI:</p>
                    <div class="mt-2 flex items-center gap-2" x-data="{ copied: false }">
                        <input type="text" readonly value="{{ $googleRedirectUrl }}" id="google-redirect-url"
                               class="w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-background)] px-3 py-2 text-sm text-[var(--color-muted)]">
                        <button type="button" class="btn btn-ghost shrink-0"
                                @click="navigator.clipboard.writeText('{{ $googleRedirectUrl }}'); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Save the Client ID and Secret above first, then test.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="btn btn-secondary" @click="run()" :disabled="busy">
                            <span x-show="!busy">Test connection</span>
                            <span x-show="busy" x-cloak>Testing…</span>
                        </button>
                    </div>
                    <p x-show="message" x-cloak class="mt-2 text-sm"
                       :class="ok ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]'" x-text="message"></p>
                </div>
            @endif

            @if($tab === 'security')
                <div class="mt-6 border-t border-[var(--color-border)] pt-6" x-data="connectionTest('{{ route('reddy.settings.test-turnstile') }}')">
                    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Test Turnstile connection</h3>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Validates your Turnstile secret key against Cloudflare. Make sure you've saved the keys first.</p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" @click="run()" :disabled="busy">
                            <span x-show="!busy">Test connection</span>
                            <span x-show="busy" x-cloak>Testing…</span>
                        </button>
                        <p x-show="message" x-cloak class="mt-2 text-sm"
                           :class="ok ? 'text-[var(--color-success)]' : 'text-[var(--color-danger)]'" x-text="message"></p>
                    </div>
                </div>
            @endif

            {{-- Sticky save bar --}}
            <div class="sticky bottom-4 mt-8 flex items-center justify-between gap-4 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4 shadow-lg">
                <p class="text-sm text-[var(--color-muted)]" x-show="!dirty">No unsaved changes.</p>
                <p class="text-sm font-semibold text-[var(--color-warning)]" x-show="dirty" x-cloak>You have unsaved changes.</p>
                <div class="flex gap-2">
                    <button type="reset" class="btn btn-ghost" x-show="dirty" x-cloak>Discard</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>
@endif

{{-- Recent settings changes --}}
@if(count($recentChanges))
<div class="surface p-5 mt-5">
    <h3 class="font-display text-lg font-bold text-[var(--color-text)] mb-3">Recent changes</h3>
    <ul class="space-y-2.5 text-sm">
        @foreach($recentChanges as $log)
        <li class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="text-[var(--color-muted)]">{{ $log->created_at->format('d M Y, h:i A') }}</span>
            <span class="font-semibold text-[var(--color-text)]">{{ $log->admin->name ?? '—' }}</span>
            <span class="text-[var(--color-muted)]">updated {{ count($log->new_values ?? []) }} setting(s)</span>
            <span class="font-mono text-xs text-[var(--color-muted)]">{{ implode(', ', array_keys($log->new_values ?? [])) }}</span>
        </li>
        @endforeach
    </ul>
</div>
@endif
@endsection

@push('scripts')
<script>
function settingsForm() {
    return {
        dirty: false,
        init() {
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) { e.preventDefault(); }
            });
        }
    };
}
function connectionTest(url, withEmail = false) {
    return {
        busy: false, ok: false, message: '',
        email: '{{ auth('admin')->user()->email }}',
        async run() {
            this.busy = true; this.message = '';
            try {
                const body = new FormData();
                if (withEmail) body.append('email', this.email);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body
                });
                const data = await res.json();
                this.ok = !!data.ok;
                this.message = data.message || (data.ok ? 'Done.' : 'Something went wrong.');
            } catch (err) {
                this.ok = false;
                this.message = 'Request failed: ' + err.message;
            } finally {
                this.busy = false;
            }
        }
    };
}
</script>
@endpush
