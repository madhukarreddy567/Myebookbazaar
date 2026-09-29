@extends('layouts.admin')

@section('title', 'Confirm update')
@section('page_title', 'Confirm update')
@section('breadcrumb', 'System / Updates / Confirm')

@section('content')
<x-admin.page-header title="Confirm update" subtitle="Read carefully before installing." />

<div class="surface p-5 max-w-3xl">
    <h3 class="font-display text-xl font-bold text-[var(--color-text)]">What will happen</h3>
    <dl class="mt-4 space-y-3 text-sm">
        <div class="flex justify-between gap-4">
            <dt style="color: var(--color-muted)">Currently installed</dt>
            <dd class="font-mono text-[var(--color-text)]">{{ $updates->installedVersion() }} ({{ $updates->installedSha() ? substr($updates->installedSha(), 0, 12) : 'unknown' }})</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt style="color: var(--color-muted)">Will install</dt>
            <dd class="font-mono font-semibold text-[var(--color-text)]">{{ $release['name'] ?? $release['tag'] ?? substr($sha, 0, 12) }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt style="color: var(--color-muted)">Commit</dt>
            <dd class="font-mono text-xs text-[var(--color-text)] break-all">{{ $sha }}</dd>
        </div>
    </dl>

    @if(!empty($release['notes']))
        <h4 class="mt-5 text-sm font-semibold text-[var(--color-text)]">Changelog</h4>
        <div class="mt-2 max-h-64 overflow-y-auto rounded-xl border border-[var(--color-border)] p-3 text-sm whitespace-pre-line" style="color: var(--color-text)">{{ $release['notes'] }}</div>
    @endif

    <div class="mt-5 rounded-xl border p-4 text-sm" style="border-color: var(--color-warning); background: color-mix(in srgb, var(--color-warning) 8%, transparent)">
        <p class="font-semibold" style="color: var(--color-warning)">⚠ Backup warning</p>
        <ul class="mt-2 space-y-1 list-disc list-inside" style="color: var(--color-text)">
            <li>A full backup (database + files) is taken automatically right before the update.</li>
            <li>The site shows a maintenance page to visitors while the update applies (usually under a minute).</li>
            <li>If anything fails, the backup is restored automatically and the site comes back online.</li>
            <li>Your <code class="font-mono">.env</code>, uploaded books, covers and existing backups are never overwritten.</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('reddy.updates.apply') }}" class="mt-5">
        @csrf
        <input type="hidden" name="sha" value="{{ $sha }}">
        <label class="flex items-start gap-3 text-sm cursor-pointer" style="color: var(--color-text)">
            <input type="checkbox" name="confirm_backup" value="1" required class="mt-1">
            <span>I understand a backup will be taken, the site will briefly go into maintenance mode, and the update will be rolled back automatically if it fails.</span>
        </label>
        @error('confirm_backup')<p class="field-error mt-1"><x-admin.icon name="alert" class="w-4 h-4" />{{ $message }}</p>@enderror

        <div class="mt-4">
            <label for="password" class="block text-sm font-semibold mb-1" style="color: var(--color-text)">Re-enter your admin password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" class="input w-full max-w-md" placeholder="Your admin password">
            <p class="mt-1 text-xs" style="color: var(--color-muted)">Required to authorize the update. It is checked once and never stored.</p>
            @error('password')<p class="field-error mt-1"><x-admin.icon name="alert" class="w-4 h-4" />{{ $message }}</p>@enderror
        </div>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="btn btn-primary">Install update now</button>
            <a href="{{ route('reddy.updates.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
