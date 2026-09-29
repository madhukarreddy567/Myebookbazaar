@extends('layouts.app')

@section('title', 'My orders — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My orders']]" />
    <h1 class="display-lg text-teal-900 uppercase mt-3">My orders</h1>

    @if(session('status'))
        <x-ui.alert type="info" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mt-6">
        @if($orders->isEmpty())
            <x-ui.card>
                <x-ui.empty-state icon="file-text" title="No orders yet"
                    text="When you buy an ebook, it will appear here."
                    :action-url="route('books')" action-label="Browse ebooks" />
            </x-ui.card>
        @else
            <div class="space-y-4">
                @foreach($orders as $order)
                <x-ui.card class="p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <a href="{{ route('orders.show', $order) }}" class="font-extrabold text-lg hover:underline">{{ $order->order_number }}</a>
                            <p class="text-sm text-mutedtext">
                                {{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} ·
                                {{ $order->items->count() }} {{ Str::plural('ebook', $order->items->count()) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-ui.badge :type="match($order->status) {
                                'paid' => 'success', 'pending' => 'warning',
                                'failed' => 'danger', default => 'neutral',
                            }">{{ $order->statusLabel() }}</x-ui.badge>
                            <span class="price text-xl">{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</span>
                        </div>
                    </div>
                </x-ui.card>
                @endforeach
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
