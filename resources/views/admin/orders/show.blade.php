@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)
@section('page_title', $order->order_number)
@section('breadcrumb', 'Sales / Orders / ' . $order->order_number)

@section('content')
<x-admin.page-header title="{{ $order->order_number }}" subtitle="Placed {{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST">
    <x-admin.badge :type="match($order->status) {
        'paid' => 'success', 'pending' => 'warning',
        'failed' => 'danger', default => 'neutral',
    }">{{ $order->statusLabel() }}</x-admin.badge>
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
        {{-- Items --}}
        <div class="surface overflow-hidden">
            <div class="p-4 border-b" style="border-color: var(--color-border)"><h2 class="font-extrabold text-lg">Items</h2></div>
            <table class="data-table">
                <thead><tr><th scope="col">Ebook</th><th scope="col" class="text-right">Qty</th><th scope="col" class="text-right">Unit price</th><th scope="col" class="text-right">Line total</th></tr></thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td class="text-right tabular-nums">{{ $item->quantity }}</td>
                        <td class="text-right tabular-nums">{{ $item->currency === 'INR' ? '₹' : '$' }}{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-right tabular-nums font-semibold">{{ $item->currency === 'INR' ? '₹' : '$' }}{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <dl class="p-4 space-y-1 text-sm max-w-xs ml-auto">
                <div class="flex justify-between"><dt style="color: var(--color-muted)">Subtotal</dt><dd class="tabular-nums">{{ $order->currency }} {{ number_format((float) $order->subtotal, 2) }}</dd></div>
                <div class="flex justify-between"><dt style="color: var(--color-muted)">Discount</dt><dd class="tabular-nums">{{ $order->currency }} {{ number_format((float) $order->discount_total, 2) }}</dd></div>
                <div class="flex justify-between font-extrabold text-base pt-2 border-t" style="border-color: var(--color-border)"><dt>Total</dt><dd class="tabular-nums">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</dd></div>
            </dl>
        </div>

        {{-- Payments --}}
        <div class="surface overflow-hidden">
            <div class="p-4 border-b" style="border-color: var(--color-border)"><h2 class="font-extrabold text-lg">Payments</h2></div>
            @if($order->payments->isNotEmpty())
            <table class="data-table">
                <thead><tr><th scope="col">Razorpay order</th><th scope="col">Razorpay payment</th><th scope="col" class="text-right">Amount</th><th scope="col">Status</th><th scope="col">At</th></tr></thead>
                <tbody>
                    @foreach($order->payments as $payment)
                    <tr>
                        <td class="text-xs font-mono">{{ $payment->razorpay_order_id ?? '—' }}</td>
                        <td class="text-xs font-mono">{{ $payment->razorpay_payment_id ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                        <td><x-admin.badge :type="$payment->isCaptured() ? 'success' : ($payment->status === 'failed' ? 'danger' : 'warning')">{{ ucfirst($payment->status) }}</x-admin.badge></td>
                        <td class="text-sm whitespace-nowrap">{{ $payment->created_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <x-admin.empty-state title="No payments yet" text="No Razorpay payment attempt has been recorded for this order." />
            @endif
        </div>

        {{-- Download logs --}}
        <div class="surface overflow-hidden">
            <div class="p-4 border-b" style="border-color: var(--color-border)"><h2 class="font-extrabold text-lg">Download logs</h2></div>
            @if($order->downloadLogs->isNotEmpty())
            <table class="data-table">
                <thead><tr><th scope="col">Ebook</th><th scope="col">IP</th><th scope="col">Downloaded at</th></tr></thead>
                <tbody>
                    @foreach($order->downloadLogs as $log)
                    <tr>
                        <td>{{ $log->book->title ?? '—' }}</td>
                        <td class="text-sm font-mono">{{ $log->ip_address }}</td>
                        <td class="text-sm whitespace-nowrap">{{ $log->downloaded_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <x-admin.empty-state title="No downloads yet" text="Each download is logged here with the IP address." />
            @endif
        </div>
    </div>

    <div class="space-y-4">
        {{-- Customer --}}
        <div class="surface p-5">
            <h2 class="font-extrabold text-lg">Customer</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Name</dt><dd class="font-medium text-right">{{ $order->user->name ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Email</dt><dd class="font-medium text-right break-all">{{ $order->user->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Country</dt><dd class="font-medium">{{ $order->user->country_name ?? $order->user->country_code ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">Method</dt><dd class="font-medium">{{ $order->payment_method === 'manual' ? 'Manual' : 'Razorpay' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">No-refund accepted</dt><dd class="font-medium">{{ $order->no_refund_accepted ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--color-muted)">IP</dt><dd class="font-mono text-xs">{{ $order->ip_address ?? '—' }}</dd></div>
            </dl>
        </div>

        {{-- Manual mark as paid --}}
        @if(!$order->isPaid() && $order->status !== 'cancelled')
        <div class="surface p-5 border-l-4" style="border-left-color: var(--color-danger)">
            <h2 class="font-extrabold text-lg">Mark as paid</h2>
            <p class="text-sm mt-1" style="color: var(--color-muted)">For payments received outside Razorpay (bank transfer, cash). A note is required and this action is audited.</p>
            <form method="POST" action="{{ route('reddy.orders.mark-paid', $order) }}" class="mt-3">
                @csrf
                <label for="note" class="field-label">Note (required, min 10 characters)</label>
                <textarea id="note" name="note" rows="3" class="input" required minlength="10" maxlength="1000"
                    placeholder="e.g. UPI payment of ₹149 received on 29 Sep, UTR 123456">{{ old('note') }}</textarea>
                <button type="submit" class="btn btn-danger w-full mt-3"
                    onclick="return confirm('Mark order {{ $order->order_number }} as paid? This cannot be undone.')">
                    Mark as paid
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
