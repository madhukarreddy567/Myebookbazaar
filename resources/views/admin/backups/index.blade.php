@extends('layouts.admin')

@section('title', 'Backups')
@section('page_title', 'Backups')
@section('breadcrumb', 'System / Backups')

@section('content')
<x-admin.page-header title="Backups" subtitle="Full database + file snapshots. Restoring replaces the current code and database — a pre-restore backup is strongly recommended.">
    <form method="POST" action="{{ route('reddy.backups.store') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Create backup now</button>
    </form>
</x-admin.page-header>

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

<div class="surface overflow-hidden">
    @if($backups->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Created (IST)</th><th>File</th><th>Type</th><th>Size</th><th>Version</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @foreach($backups as $backup)
                <tr>
                    <td class="text-sm whitespace-nowrap" style="color: var(--color-muted)">{{ $backup->created_at?->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') ?? '—' }}</td>
                    <td class="font-mono text-xs">{{ $backup->filename }}</td>
                    <td><span class="text-xs font-semibold px-2 py-1 rounded-full" style="background: var(--color-surface-alt)">{{ $backup->typeLabel() }}</span></td>
                    <td class="text-sm">{{ $backup->sizeLabel() }}</td>
                    <td class="text-sm font-mono">{{ $backup->version ?? '—' }}</td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('reddy.backups.download', $backup) }}" class="btn btn-ghost btn-sm">Download</a>
                        <button type="button" class="btn btn-secondary btn-sm"
                                onclick="if(document.getElementById('restore-{{ $backup->id }}').showModal()){}">Restore</button>
                        <form method="POST" action="{{ route('reddy.backups.destroy', $backup) }}" class="inline"
                              onsubmit="return confirm('Delete this backup permanently?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--color-danger)">Delete</button>
                        </form>

                        <dialog id="restore-{{ $backup->id }}" class="rounded-2xl p-6 max-w-md" style="background: var(--color-surface); color: var(--color-text)">
                            <h3 class="font-display text-xl font-bold">Restore this backup?</h3>
                            <p class="mt-2 text-sm" style="color: var(--color-muted)">
                                This replaces the current code and database with the backup from
                                {{ $backup->created_at?->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST.
                                Anything changed since then will be lost.
                            </p>
                            <form method="POST" action="{{ route('reddy.backups.restore', $backup) }}" class="mt-4">
                                @csrf
                                <label class="flex items-start gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="confirm_restore" value="1" required class="mt-1">
                                    <span>I understand this overwrites the live site and database.</span>
                                </label>
                                <div class="mt-4 flex gap-2 justify-end">
                                    <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Restore now</button>
                                </div>
                            </form>
                        </dialog>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $backups->links() }}</div>
    @else
    <x-admin.empty-state title="No backups yet." hint="Create your first backup above, or wait for the daily automatic backup." icon="database" />
    @endif
</div>
@endsection
