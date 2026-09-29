@extends('layouts.app')

@section('title', 'Your cart — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-4">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Your cart']]" />
    <h1 class="display-lg text-teal-900 uppercase mt-3">
        Your cart
        @if($items->isNotEmpty())
            <span class="badge badge-sale align-middle ml-1">{{ $items->count() }} {{ Str::plural('item', $items->count()) }}</span>
        @endif
    </h1>

    @if(session('status'))
        <x-ui.alert type="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if($items->isEmpty())
        <x-ui.card class="mt-5">
            <x-ui.empty-state icon="cart" title="Your cart is empty"
                text="Browse ebooks and add your first title."
                :action-url="route('books')" action-label="Browse ebooks" />
        </x-ui.card>
    @else
        <div class="grid lg:grid-cols-[1.6fr_1fr] gap-5 mt-5 items-start">
            {{-- Items --}}
            <div class="flex flex-col gap-3">
                @foreach($items as $row)
                <x-ui.card class="p-4 flex gap-4 items-center">
                    <a href="{{ route('books.show', $row->book->slug) }}" class="w-16 shrink-0" tabindex="-1" aria-hidden="true">
                        @if($row->book->coverUrl())
                            <img src="{{ $row->book->coverUrl() }}" alt="" class="rounded-lg w-full h-auto" loading="lazy">
                        @elseif(str_contains($row->book->slug, 'fit-at-home'))
                            <img src="{{ asset('images/stitch/book-mockup-3d.jpg') }}" alt="" class="rounded-lg w-full h-auto" loading="lazy">
                        @else
                            <div class="book-cover-placeholder aspect-[3/4] rounded-lg" role="img" aria-label="Cover coming soon">
                                <span class="text-xs">{{ $row->book->title }}</span>
                            </div>
                        @endif
                    </a>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('books.show', $row->book->slug) }}" class="font-bold text-[17px] hover:underline line-clamp-2">{{ $row->book->title }}</a>
                        <p class="text-sm text-mutedtext mt-0.5">Digital PDF ebook &bull; Lifetime access</p>
                        <form method="POST" action="{{ route('cart.destroy', $row) }}" class="mt-1.5"
                              onsubmit="return confirm('Remove this ebook from your cart?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-danger underline underline-offset-2">Remove</button>
                        </form>
                    </div>
                    <p class="price text-xl shrink-0 font-display font-extrabold">{{ $row->price_display }}</p>
                </x-ui.card>
                @endforeach
                <a href="{{ route('books') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-teal-700 hover:underline mt-1">
                    <x-ui.icon name="arrow-right" class="w-4 h-4 rotate-180" /> Continue browsing
                </a>
            </div>

            {{-- Summary --}}
            <x-ui.card class="p-5 lg:sticky lg:top-24">
                <h2 class="display-sm text-teal-900 uppercase">Summary</h2>

                {{-- Coupon: input is always empty; never pre-filled --}}
                @auth
                    @if($couponsEnabled && settings_enabled('features.checkout', true) && Route::has('checkout.coupon.apply'))
                    <div class="mt-4">
                        @if($couponCode)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-tint-teal px-4 py-3">
                                <p class="text-sm">
                                    <x-ui.badge>Coupon</x-ui.badge>
                                    <strong class="ml-1">{{ $couponCode }}</strong>
                                    <span class="text-mutedtext">applied</span>
                                </p>
                                <form method="POST" action="{{ route('checkout.coupon.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-teal-700 underline hover:text-inktext">Remove</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('checkout.coupon.apply') }}" class="flex gap-2">
                                @csrf
                                <x-ui.input name="code" placeholder="Coupon code" :value="old('code')" :error="$errors->first('coupon')" class="flex-1" aria-label="Coupon code" />
                                <x-ui.button type="submit" variant="secondary">Apply</x-ui.button>
                            </form>
                            @if($errors->has('coupon'))
                                <p class="field-error mt-1" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('coupon') }}</span></p>
                            @endif
                        @endif
                    </div>
                    @endif
                @endauth

                <dl class="mt-4 space-y-2 text-[15px]">
                    @foreach($summary['lines'] as $line)
                    <div class="flex justify-between gap-3">
                        <dt class="text-mutedtext line-clamp-1">{{ $line['title'] }}</dt>
                        <dd class="price shrink-0">{{ $line['display'] }}</dd>
                    </div>
                    @endforeach
                </dl>
                <div class="border-t border-line mt-4 pt-4 flex justify-between items-baseline">
                    <span class="font-bold">Total</span>
                    <span class="font-display text-3xl font-extrabold text-teal-900">{{ $summary['display_total'] }}</span>
                </div>
                <p class="text-xs text-mutedtext mt-1.5">
                    @if($summary['currency'] === 'INR')
                        Charged in Indian rupees.
                    @else
                        Charged in US dollars.
                    @endif
                    All sales are final — <a href="{{ route('no-refund') }}" class="underline">no-refund policy</a>.
                </p>

                <div class="mt-5">
                    @guest
                        <x-ui.button href="{{ route('login') }}" size="lg" class="w-full btn-glow">Log in to check out</x-ui.button>
                        <p class="text-sm text-mutedtext mt-2 text-center">You need an account before you can pay.</p>
                    @else
                        @if(settings_enabled('features.checkout', true) && Route::has('checkout'))
                            <x-ui.button href="{{ route('checkout') }}" size="lg" class="w-full btn-glow uppercase tracking-wide">Proceed to checkout</x-ui.button>
                            <p class="flex items-center justify-center gap-1.5 mt-2.5 text-xs text-mutedtext">
                                <x-ui.icon name="lock" class="w-4 h-4" /> Secure checkout &bull; Instant access after payment
                            </p>
                        @else
                            <x-ui.alert type="info">Online checkout is being set up. Your cart is saved and ready.</x-ui.alert>
                        @endif
                    @endguest
                </div>
            </x-ui.card>
        </div>
    @endif
</div>
@endsection
