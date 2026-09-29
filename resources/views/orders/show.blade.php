@extends('layouts.app')

@section('title', 'Order ' . $order->order_number . ' — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My orders', 'url' => route('orders.index')], ['label' => $order->order_number]]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mt-4 no-print">
        <h1 class="display-md text-teal-900 uppercase">{{ $order->order_number }}</h1>
        <div class="flex items-center gap-3">
            <x-ui.badge :type="match($order->status) {
                'paid' => 'success', 'pending' => 'warning',
                'failed' => 'danger', default => 'neutral',
            }">{{ $order->statusLabel() }}</x-ui.badge>
            <x-ui.button variant="secondary" size="sm" onclick="window.print()">Print invoice</x-ui.button>
        </div>
    </div>

    @if(session('status'))
        <x-ui.alert type="info" class="mt-4 no-print">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-8 mt-6 items-start">
        <x-ui.card class="p-6">
            <h2 class="text-2xl font-extrabold">Invoice</h2>
            <div class="mt-2 text-sm text-mutedtext">
                <p class="font-heading font-bold text-lg text-inktext">{{ $siteName }}</p>
                @if($supportEmail)<p>{{ $supportEmail }}</p>@endif
                <p class="mt-2">Billed to: {{ $order->user->name }} ({{ $order->user->email }})</p>
                <p>Date: {{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</p>
            </div>

            <table class="w-full mt-4 text-sm">
                <thead>
                    <tr class="text-left text-mutedtext border-b border-line">
                        <th class="py-2 pr-2 font-medium">Ebook</th>
                        <th class="py-2 px-2 font-medium text-right">Qty</th>
                        <th class="py-2 px-2 font-medium text-right">Price</th>
                        <th class="py-2 pl-2 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr class="border-b border-line last:border-0">
                        <td class="py-3 pr-2 font-medium">{{ $item->title }}</td>
                        <td class="py-3 px-2 text-right">{{ $item->quantity }}</td>
                        <td class="py-3 px-2 text-right price">{{ app(\App\Services\PriceService::class)->format((float) $item->unit_price, $item->currency) }}</td>
                        <td class="py-3 pl-2 text-right price">{{ app(\App\Services\PriceService::class)->format((float) $item->line_total, $item->currency) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <dl class="mt-4 space-y-1 text-sm max-w-xs ml-auto">
                <div class="flex justify-between"><dt class="text-mutedtext">Subtotal</dt><dd class="price">{{ app(\App\Services\PriceService::class)->format((float) $order->subtotal, $order->currency) }}</dd></div>
                @if((float) $order->discount_total > 0)
                <div class="flex justify-between"><dt class="text-mutedtext">Discount</dt><dd class="price">−{{ app(\App\Services\PriceService::class)->format((float) $order->discount_total, $order->currency) }}</dd></div>
                @endif
                <div class="flex justify-between text-lg font-extrabold pt-2 border-t border-line"><dt>Total</dt><dd class="price">{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-mutedtext">All amounts in {{ $order->currency === 'INR' ? 'Indian rupees' : 'US dollars' }}. Digital sale — final and non-refundable.</p>
        </x-ui.card>

        <x-ui.card class="p-6 no-print">
            <h2 class="text-xl font-extrabold">Payment</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-mutedtext">Method</dt><dd class="font-medium">{{ $order->payment_method === 'manual' ? 'Manual' : 'Razorpay' }}</dd></div>
                <div class="flex justify-between"><dt class="text-mutedtext">Status</dt><dd>{{ $order->statusLabel() }}</dd></div>
                @if($order->no_refund_accepted)
                <div class="flex justify-between"><dt class="text-mutedtext">No-refund accepted</dt><dd class="font-medium">Yes</dd></div>
                @endif
            </dl>

            @if($order->isPaid())
                <x-ui.button href="{{ route('library') }}" class="w-full mt-4">Go to My Library</x-ui.button>
            @elseif($order->status === 'pending')
                <x-ui.alert type="info" class="mt-4">Your payment is being confirmed. If you just paid, this page will update shortly.</x-ui.alert>
                <x-ui.button variant="secondary" href="{{ route('cart.index') }}" class="w-full mt-3">Back to cart</x-ui.button>
            @else
                <x-ui.alert type="danger" class="mt-4">This payment did not go through. No money was taken for this order.</x-ui.alert>
                <x-ui.button href="{{ route('cart.index') }}" class="w-full mt-3">Try again</x-ui.button>
            @endif
        </x-ui.card>
    </div>
</div>

<style>
@media print {
    .no-print, header, footer, nav { display: none !important; }
    body { background: #fff; }
}
</style>
@endsection
