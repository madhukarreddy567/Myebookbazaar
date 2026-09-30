@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'My orders — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My orders']]" />
    <h1 class="st-h text-[34px] md:text-[42px] mt-3">My orders</h1>

    @if(session('status'))
        <x-ui.alert type="info" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mt-6">
        @if($orders->isEmpty())
            <div class="st-card st-empty">
                <x-ui.empty-state icon="file-text" title="No orders yet"
                    text="When you buy an ebook, it will appear here."
                    :action-url="route('books')" action-label="Browse ebooks" />
            </div>
        @else
            <div class="space-y-4">
                @foreach($orders as $order)
                <div class="st-card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <a href="{{ route('orders.show', $order) }}" class="font-extrabold text-lg hover:underline">{{ $order->order_number }}</a>
                            <p class="text-sm st-muted">
                                {{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} ·
                                {{ $order->items->count() }} {{ Str::plural('ebook', $order->items->count()) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider
                                @if($order->status === 'paid') bg-[#E9F7EE] text-[#1E8E5A]
                                @elseif($order->status === 'pending') bg-[#FFF4E5] text-[#B45309]
                                @elseif($order->status === 'failed') bg-[#FDECEC] text-[#B3372F]
                                @else bg-[#EDF1F7] text-[#5C6B75] @endif">
                                {{ $order->statusLabel() }}
                            </span>
                            <span class="text-xl font-extrabold text-[#0B3D4A]">{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
