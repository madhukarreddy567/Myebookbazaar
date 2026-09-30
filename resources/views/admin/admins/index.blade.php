@extends('layouts.admin')

@section('title', 'Admin accounts')
@section('page_title', 'Admin accounts')
@section('breadcrumb', 'System / Admins')

@section('content')
<x-admin.page-header title="Admin accounts" subtitle="Staff can manage the store but never settings, updates, or other admins." />

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-danger)">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

<div class="grid xl:grid-cols-3 gap-4">
    <div class="surface p-6">
        <h3 class="font-display text-xl font-bold mb-4">Create staff account</h3>
        <form method="POST" action="{{ route('reddy.admins.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="field-label">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="input" autocomplete="off">
            </div>
            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="input" autocomplete="off">
            </div>
            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" name="password" type="password" required minlength="12" class="input" autocomplete="new-password">
                <p class="field-hint">Minimum 12 characters and at least one symbol. Share it securely — it is stored hashed.</p>
            </div>
            <button type="submit" class="btn btn-primary w-full">Create staff account</button>
        </form>
    </div>

    <div class="surface p-6 xl:col-span-2">
        <h3 class="font-display text-xl font-bold mb-4">All admins</h3>
        <div class="overflow-x-auto -mx-6 px-6">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Created</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach($admins as $admin)
                    <tr>
                        <td>
                            <p class="font-semibold">{{ $admin->name }}</p>
                            <p class="text-xs" style="color: var(--color-muted)">{{ $admin->email }}</p>
                        </td>
                        <td><x-admin.badge :status="$admin->isSuperAdmin() ? 'super_admin' : 'staff'">{{ $admin->isSuperAdmin() ? 'Super Admin' : 'Staff' }}</x-admin.badge></td>
                        <td><x-admin.badge :status="$admin->is_active ? 'active' : 'disabled'" /></td>
                        <td class="text-sm" style="color: var(--color-muted)">{{ $admin->created_at->setTimezone('Asia/Kolkata')->format('d M Y') }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if($admin->id !== $current->id)
                            <form method="POST" action="{{ route('reddy.admins.toggle', $admin) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-sm font-semibold text-[var(--color-teal-700)] hover:underline">{{ $admin->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <span class="mx-1 text-[var(--color-border)]">|</span>
                            <form method="POST" action="{{ route('reddy.admins.destroy', $admin) }}" class="inline" onsubmit="return confirm('Delete {{ $admin->email }}? This cannot be undone.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-semibold hover:underline" style="color: var(--color-danger)">Delete</button>
                            </form>
                            @else
                            <span class="text-xs" style="color: var(--color-muted)">This is you</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $admins->links() }}</div>
    </div>
</div>
@endsection
