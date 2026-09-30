@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'My orders — MyEbookBazaar')

@section('content')
<div class="st-page w-full max-w-[1120px] mx-auto px-4 sm:px-6 py-8 sm:py-12">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6">
        <div class="space-y-1">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-container/10 text-primary-container font-label-sm text-label-sm tracking-wide uppercase font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-primary-container"></span>
                Purchase History
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight font-bold">My Orders</h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-xl">
                Every purchase, invoice and payment status — in one place.
            </p>
        </div>
        <a href="{{ route('account') }}" class="inline-flex items-center gap-2 self-start sm:self-auto font-label-md text-label-md text-on-surface-variant hover:text-on-surface transition-colors py-2 px-1">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to Account</span>
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="bg-surface-container-lowest rounded-xl p-10 sm:p-14 shadow-sm text-center">
            <div class="w-14 h-14 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[28px]">receipt_long</span>
            </div>
            <h2 class="font-headline-md text-headline-md text-on-surface font-bold">No orders yet</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-md mx-auto">
                When you buy an ebook, it will appear here.
            </p>
            <a href="{{ route('books') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm mt-6">
                <span class="material-symbols-outlined text-[18px]">storefront</span>
                Browse ebooks
            </a>
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block bg-surface-container-lowest rounded-DEFAULT border border-surface-container-high overflow-hidden shadow-sm">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/70 border-b border-surface-container-high">
                        <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant">Order</th>
                        <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant">Date</th>
                        <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant">Items</th>
                        <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant text-right">Amount</th>
                        <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant text-center">Status</th>
                        <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container-high/60 font-body-sm text-body-sm">
                    @foreach($orders as $order)
                    <tr class="hover:bg-surface/50 transition-colors">
                        <td class="py-4 px-5">
                            <a href="{{ route('orders.show', $order) }}" class="font-medium text-on-surface hover:text-primary hover:underline whitespace-nowrap">#{{ $order->order_number }}</a>
                        </td>
                        <td class="py-4 px-5 text-on-surface-variant whitespace-nowrap">{{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                        <td class="py-4 px-5">
                            <span class="font-medium text-on-surface line-clamp-1">{{ $order->items->first()?->title ?? '—' }}</span>
                            @if($order->items->count() > 1)
                            <span class="text-on-surface-variant">+{{ $order->items->count() - 1 }} more</span>
                            @endif
                        </td>
                        <td class="py-4 px-5 text-right font-medium text-on-surface whitespace-nowrap">{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</td>
                        <td class="py-4 px-5 text-center">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-label-sm font-label-sm font-semibold
                                @if($order->status === 'paid') bg-secondary-container/60 text-secondary
                                @elseif($order->status === 'pending') bg-primary-fixed/60 text-on-primary-fixed-variant
                                @else bg-surface-container text-on-surface-variant @endif">
                                <span class="w-1.5 h-1.5 rounded-full
                                    @if($order->status === 'paid') bg-secondary
                                    @elseif($order->status === 'pending') bg-primary-container
                                    @else bg-on-surface-variant @endif"></span>
                                {{ $order->statusLabel() }}
                            </span>
                        </td>
                        <td class="py-4 px-5 text-right whitespace-nowrap">
                            <a class="inline-flex items-center gap-1 text-primary hover:text-on-primary-container font-label-md text-label-md font-medium hover:underline transition-colors" href="{{ route('orders.show', $order) }}">
                                <span>View Invoice</span>
                                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-4">
            @foreach($orders as $order)
            <div class="bg-surface-container-lowest p-5 rounded-DEFAULT border border-surface-container-high shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-surface-container-high">
                    <div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Order #{{ $order->order_number }}</span>
                        <p class="font-body-sm text-body-sm text-on-surface font-medium">{{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-label-sm font-label-sm font-semibold
                        @if($order->status === 'paid') bg-secondary-container/60 text-secondary
                        @elseif($order->status === 'pending') bg-primary-fixed/60 text-on-primary-fixed-variant
                        @else bg-surface-container text-on-surface-variant @endif">
                        <span class="w-1.5 h-1.5 rounded-full
                            @if($order->status === 'paid') bg-secondary
                            @elseif($order->status === 'pending') bg-primary-container
                            @else bg-on-surface-variant @endif"></span>
                        {{ $order->statusLabel() }}
                    </span>
                </div>
                <div>
                    <h3 class="font-headline-sm text-base font-semibold text-on-surface line-clamp-1">{{ $order->items->first()?->title ?? '—' }}</h3>
                    <p class="font-label-md text-label-md font-bold text-on-surface mt-0.5">{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</p>
                </div>
                <div class="pt-2">
                    <a class="w-full py-2.5 px-4 rounded-full border border-outline-variant text-on-surface font-label-md text-label-md hover:bg-surface-container-low transition-colors text-center block" href="{{ route('orders.show', $order) }}">
                        View Invoice &amp; Receipt
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
