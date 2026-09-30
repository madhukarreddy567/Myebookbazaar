@extends('layouts.admin')

@section('title', 'Ticket #' . $ticket->id)
@section('page_title', 'Ticket #' . $ticket->id)
@section('breadcrumb', 'Sales / Support / #' . $ticket->id)

@section('content')
<x-admin.page-header title="Ticket #{{ $ticket->id }} — {{ $ticket->subject }}" subtitle="Opened {{ $ticket->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST">
    <x-admin.badge :status="match($ticket->status) {
        'open' => 'pending',
        'awaiting_customer' => 'active',
        'awaiting_support' => 'pending',
        'closed' => 'refunded',
        default => 'inactive',
    }">{{ $ticket->statusLabel() }}</x-admin.badge>
    @if($ticket->isClosed())
    <form method="POST" action="{{ route('reddy.tickets.reopen', $ticket) }}" class="inline">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm">Reopen</button>
    </form>
    @else
    <form method="POST" action="{{ route('reddy.tickets.close', $ticket) }}" class="inline">
        @csrf
        <button type="submit" class="btn btn-ghost btn-sm">Close ticket</button>
    </form>
    @endif
</x-admin.page-header>

@if(session('status'))
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-success)">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="surface p-4 mb-4 text-sm font-semibold" style="color: var(--color-danger)">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

<div class="grid lg:grid-cols-[1.6fr_1fr] gap-4 items-start">
    <div class="space-y-4">
        {{-- Thread --}}
        <div class="surface p-5">
            <h2 class="font-extrabold text-lg mb-4">Thread</h2>
            <div class="space-y-4">
                @foreach($ticket->replies as $reply)
                <div class="p-4 rounded-lg" style="background: {{ $reply->is_admin ? 'var(--color-teal-50, #F0F7F8)' : 'var(--color-surface-2, #F7F9FB)' }}; border: 1px solid var(--color-border)">
                    <div class="flex items-center justify-between gap-3 mb-1.5">
                        <p class="font-bold text-sm">
                            @if($reply->is_admin)
                            <x-admin.badge status="active">Support</x-admin.badge>
                            @else
                            {{ $reply->authorName() }}
                            @endif
                        </p>
                        <p class="text-xs" style="color: var(--color-muted)">{{ $reply->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="text-sm leading-relaxed whitespace-pre-line">{{ $reply->body }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Reply --}}
        <div class="surface p-5">
            <h2 class="font-extrabold text-lg mb-3">Reply</h2>
            <form method="POST" action="{{ route('reddy.tickets.reply', $ticket) }}">
                @csrf
                <label for="body" class="field-label">Message to the customer</label>
                <textarea id="body" name="body" rows="5" maxlength="5000" required class="input" placeholder="Write your reply…">{{ old('body') }}</textarea>
                <div class="mt-3 flex gap-2">
                    <button type="submit" class="btn btn-primary">Send reply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        {{-- Customer --}}
        <div class="surface p-5">
            <h2 class="font-extrabold text-lg">Customer</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Name</dt><dd class="font-medium text-right">{{ $ticket->user->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Email</dt><dd class="font-mono text-xs text-right break-all">{{ $ticket->user->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Category</dt><dd class="font-medium">{{ $ticket->categoryLabel() }}</dd></div>
            </dl>
        </div>

        {{-- Linked order + download tracking --}}
        @if($ticket->order)
        <div class="surface p-5">
            <h2 class="font-extrabold text-lg">Linked order</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Order</dt><dd class="font-mono text-xs"><a href="{{ route('reddy.orders.show', $ticket->order) }}" class="hover:underline">{{ $ticket->order->order_number }}</a></dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Total</dt><dd class="font-medium tabular-nums">{{ $ticket->order->currency }} {{ number_format((float) $ticket->order->grand_total, 2) }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Status</dt><dd class="font-medium">{{ $ticket->order->statusLabel() }}</dd></div>
            </dl>
        </div>

        <div class="surface p-5">
            <h2 class="font-extrabold text-lg">Download tracking</h2>
            <p class="text-xs mt-1 mb-3" style="color: var(--color-muted)">Refund validity at a glance (no-refund policy).</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Download Status</dt><dd class="font-medium text-right">
                    @if($firstDownload)
                        Downloaded at {{ $firstDownload->downloaded_at->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') }}
                    @else
                        <x-admin.badge status="pending">Not Downloaded Yet</x-admin.badge>
                    @endif
                </dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">IP Address</dt><dd class="font-mono text-xs">{{ $firstDownload->ip_address ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Total Attempts</dt><dd class="font-medium tabular-nums">{{ $downloadAttempts }}</dd></div>
            </dl>
        </div>
        @else
        <div class="surface p-5">
            <h2 class="font-extrabold text-lg">Linked order</h2>
            <p class="text-sm mt-2" style="color: var(--color-muted)">No order linked to this ticket.</p>
        </div>
        @endif
    </div>
</div>
@endsection
