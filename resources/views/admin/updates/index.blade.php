@extends('layouts.admin')

@section('title', 'Updates')
@section('page_title', 'Updates')
@section('breadcrumb', 'System / Updates')

@section('content')
<x-admin.page-header title="Updates" subtitle="Check GitHub for new commits and apply them with one click — always with a backup first and automatic rollback on failure." />

@if(session('status'))
    <div class="surface p-4 mb-4 border-l-4" style="border-left-color: var(--color-success)">
        <p class="text-sm font-medium" style="color: var(--color-success)">{{ session('status') }}</p>
    </div>
@endif
@if(session('error'))
    <div class="surface p-4 mb-4 border-l-4" style="border-left-color: var(--color-danger)">
        <p class="text-sm font-medium" style="color: var(--color-danger)">{{ session('error') }}</p>
    </div>
@endif

<div class="grid lg:grid-cols-2 gap-4">
    {{-- Current state --}}
    <div class="surface p-5">
        <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Installed version</h3>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt style="color: var(--color-muted)">Version</dt>
                <dd class="font-mono font-semibold text-[var(--color-text)]">{{ $updates->installedVersion() }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt style="color: var(--color-muted)">Commit</dt>
                <dd class="font-mono text-[var(--color-text)]">{{ $updates->installedSha() ? substr($updates->installedSha(), 0, 12) : 'not recorded' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt style="color: var(--color-muted)">Tracking</dt>
                <dd class="text-[var(--color-text)]">{{ $github->owner() }}/{{ $github->repo() }} <span style="color: var(--color-muted)">({{ $github->branch() }}, {{ $github->channel() }})</span></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt style="color: var(--color-muted)">Last check</dt>
                <dd class="text-[var(--color-text)]">
                    @if($lastCheck)
                        {{ \Carbon\Carbon::parse($lastCheck)->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST
                    @else
                        never
                    @endif
                </dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt style="color: var(--color-muted)">Disk space</dt>
                <dd class="text-[var(--color-text)]">{{ $freeSpace }}</dd>
            </div>
        </dl>

        <div class="mt-5 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('reddy.updates.check') }}">
                @csrf
                <button type="submit" class="btn btn-secondary" @disabled(! $updates->updatesEnabled())>Check for updates</button>
            </form>
            <a href="{{ route('reddy.settings', ['tab' => 'updates']) }}" class="btn btn-ghost">Update settings</a>
        </div>

        @if($guard)
            <p class="mt-4 text-sm" style="color: var(--color-warning)">⚠ {{ $guard }}</p>
        @endif
        @if($inProgress)
            <p class="mt-2 text-sm font-semibold" style="color: var(--color-warning)">An update is currently in progress.</p>
        @endif
    </div>

    {{-- Latest from GitHub --}}
    <div class="surface p-5">
        <h3 class="font-display text-xl font-bold text-[var(--color-text)]">Latest from GitHub</h3>
        @if($latestSha)
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt style="color: var(--color-muted)">Commit</dt>
                    <dd class="font-mono text-[var(--color-text)]">{{ substr($latestSha, 0, 12) }}</dd>
                </div>
                @if(!empty($release['tag']))
                    <div class="flex justify-between gap-4">
                        <dt style="color: var(--color-muted)">Release</dt>
                        <dd class="font-semibold text-[var(--color-text)]">{{ $release['name'] ?? $release['tag'] }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt style="color: var(--color-muted)">Status</dt>
                    <dd>
                        @if($updates->installedSha() && hash_equals($updates->installedSha(), $latestSha))
                            <span class="text-sm font-semibold" style="color: var(--color-success)">Up to date</span>
                        @else
                            <span class="text-sm font-semibold" style="color: var(--color-warning)">Update available</span>
                        @endif
                    </dd>
                </div>
            </dl>

            @if(!empty($release['notes']))
                <h4 class="mt-4 text-sm font-semibold text-[var(--color-text)]">Release notes</h4>
                <div class="mt-2 max-h-48 overflow-y-auto rounded-xl border border-[var(--color-border)] p-3 text-sm whitespace-pre-line" style="color: var(--color-text)">{{ $release['notes'] }}</div>
            @endif

            @if(!$guard && (!$updates->installedSha() || !hash_equals($updates->installedSha(), $latestSha)))
                <div class="mt-5">
                    <a href="{{ route('reddy.updates.confirm') }}" class="btn btn-primary">Review and install update</a>
                </div>
            @endif
        @else
            <p class="mt-4 text-sm" style="color: var(--color-muted)">No check has run yet, or the last check failed. Configure the repository and token in <a class="underline" href="{{ route('reddy.settings', ['tab' => 'updates']) }}">Settings → Updates</a>, then click “Check for updates”.</p>
        @endif
    </div>
</div>

<div class="surface p-5 mt-4">
    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">How updates work</h3>
    <ol class="mt-3 space-y-2 text-sm list-decimal list-inside" style="color: var(--color-text)">
        <li>A full backup (database + files) is created automatically before every update.</li>
        <li>The site goes into maintenance mode while the update applies.</li>
        <li>The exact commit's archive is downloaded from GitHub and verified against the announced SHA.</li>
        <li>Files are replaced (your <code class="font-mono">.env</code> and uploads are preserved), migrations run, caches clear.</li>
        <li>If anything fails, the backup is restored automatically and the site comes back up.</li>
    </ol>
</div>
@endsection
