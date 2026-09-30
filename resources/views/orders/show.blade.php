@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Order ' . $order->order_number . ' — MyEbookBazaar')

@php
$gstRateLabel = \App\Services\OrderService::gstRateLabel((float) $order->gst_rate).'%';
@endphp

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My orders', 'url' => route('orders.index')], ['label' => $order->order_number]]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mt-4 no-print">
        <h1 class="st-h text-[32px] md:text-[40px]">{{ $order->order_number }}</h1>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider
                @if($order->status === 'paid') bg-[#E9F7EE] text-[#1E8E5A]
                @elseif($order->status === 'pending') bg-[#FFF4E5] text-[#B45309]
                @elseif($order->status === 'failed') bg-[#FDECEC] text-[#B3372F]
                @else bg-[#EDF1F7] text-[#5C6B75] @endif">
                {{ $order->statusLabel() }}
            </span>
            <button type="button" onclick="window.print()" class="st-btn st-btn-teal !min-h-[44px] !px-5 !text-[14px]">Print invoice</button>
        </div>
    </div>

    @if(session('status'))
        <x-ui.alert type="info" class="mt-4 no-print">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-6 lg:gap-8 mt-6 items-start">
        <div class="st-card p-6">
            <h2 class="st-h text-[24px]">Invoice</h2>
            <div class="mt-2 text-sm st-muted">
                <p class="st-h !text-[18px]">{{ $siteName }}</p>
                @if($supportEmail)<p>{{ $supportEmail }}</p>@endif
                <p class="mt-2">Billed to: {{ $order->user->name }} ({{ $order->user->email }})</p>
                <p>Date: {{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</p>
            </div>

            <table class="w-full mt-4 text-sm">
                <thead>
                    <tr class="text-left st-muted border-b border-[#E4E8F2]">
                        <th class="py-2 pr-2 font-medium">Ebook</th>
                        <th class="py-2 px-2 font-medium text-right">Qty</th>
                        <th class="py-2 px-2 font-medium text-right">Price</th>
                        <th class="py-2 pl-2 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr class="border-b border-[#EDF1F7] last:border-0">
                        <td class="py-3 pr-2 font-medium">{{ $item->title }}</td>
                        <td class="py-3 px-2 text-right">{{ $item->quantity }}</td>
                        <td class="py-3 px-2 text-right">{{ app(\App\Services\PriceService::class)->format((float) $item->unit_price, $item->currency) }}</td>
                        <td class="py-3 pl-2 text-right">{{ app(\App\Services\PriceService::class)->format((float) $item->line_total, $item->currency) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <dl class="mt-4 space-y-1 text-sm max-w-xs ml-auto">
                <div class="flex justify-between"><dt class="st-muted">Subtotal</dt><dd>{{ app(\App\Services\PriceService::class)->format((float) $order->subtotal, $order->currency) }}</dd></div>
                @if((float) $order->discount_total > 0)
                <div class="flex justify-between"><dt class="st-muted">Discount</dt><dd>−{{ app(\App\Services\PriceService::class)->format((float) $order->discount_total, $order->currency) }}</dd></div>
                @endif
                @if((float) $order->gst_rate > 0)
                <div class="flex justify-between"><dt class="st-muted">Includes GST ({{ $gstRateLabel }})</dt><dd>{{ app(\App\Services\PriceService::class)->format((float) $order->gst_amount, $order->currency) }}</dd></div>
                @endif
                <div class="flex justify-between text-lg font-extrabold pt-2 border-t border-[#E4E8F2]"><dt>Total</dt><dd>{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</dd></div>
            </dl>
            <p class="mt-3 text-xs st-muted">All amounts in {{ $order->currency === 'INR' ? 'Indian rupees' : 'US dollars' }}. Digital sale — final and non-refundable.</p>
        </div>

        <div class="st-card p-6 no-print">
            <h2 class="st-h text-[20px]">Payment</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="st-muted">Method</dt><dd class="font-medium">{{ $order->payment_method === 'manual' ? 'Manual' : ($order->payment_method === 'coupon' ? 'Coupon (free)' : 'Razorpay') }}</dd></div>
                <div class="flex justify-between"><dt class="st-muted">Status</dt><dd>{{ $order->statusLabel() }}</dd></div>
                @if($order->no_refund_accepted)
                <div class="flex justify-between"><dt class="st-muted">No-refund accepted</dt><dd class="font-medium">Yes</dd></div>
                @endif
            </dl>

            @if($order->isPaid())
                <a href="{{ route('library') }}" class="st-btn st-btn-amber w-full mt-4">Go to My Library</a>
            @elseif($order->status === 'pending')
                <x-ui.alert type="info" class="mt-4">Your payment is being confirmed. If you just paid, this page will update shortly.</x-ui.alert>
                <a href="{{ route('cart.index') }}" class="st-btn st-btn-teal w-full mt-3">Back to cart</a>
            @else
                <x-ui.alert type="danger" class="mt-4">This payment did not go through. No money was taken for this order.</x-ui.alert>
                <a href="{{ route('cart.index') }}" class="st-btn st-btn-amber w-full mt-3">Try again</a>
            @endif
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, header, footer, nav { display: none !important; }
    body { background: #fff; }
}
</style>
@endsection
