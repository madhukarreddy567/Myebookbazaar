@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Your cart — MyEbookBazaar')

@section('content')
<div class="st-page w-full pb-16">
    <div class="max-w-[1120px] mx-auto px-4 sm:px-6 py-6">

        {{-- Heading --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm uppercase tracking-wider mb-2">
                    <span class="w-2 h-2 rounded-full bg-primary-container"></span>
                    Cart Review
                    @if($items->isNotEmpty())
                        ({{ $items->count() }} {{ Str::plural('Item', $items->count()) }})
                    @endif
                </div>
                <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Your Digital Library Basket</h1>
            </div>
            <a class="inline-flex items-center gap-1.5 font-label-md text-label-md text-secondary hover:text-primary transition-colors" href="{{ route('books') }}">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Continue shopping
            </a>
        </div>

        @if($items->isEmpty())
            {{-- Empty state --}}
            <div class="bg-surface-container-lowest rounded-DEFAULT p-10 shadow-sm text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-secondary mb-4">
                    <span class="material-symbols-outlined text-[32px]">menu_book</span>
                </div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">Your basket is empty</h3>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-sm mb-6">Discover practical guides for home fitness, cooking, and everyday skills.</p>
                <a class="inline-flex items-center justify-center h-11 px-6 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all" href="{{ route('books') }}">
                    Browse Catalog
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- Items --}}
                <div class="lg:col-span-7 flex flex-col gap-6">
                    @foreach($items as $row)
                    <div class="bg-surface-container-lowest rounded-DEFAULT p-6 shadow-sm flex flex-col sm:flex-row gap-6 items-start sm:items-center relative">
                        <div class="relative w-20 flex-shrink-0 aspect-[3/4] rounded-sm overflow-hidden bg-surface-container shadow-sm">
                            @if($row->book->displayCoverUrl())
                                <img src="{{ $row->book->displayCoverUrl() }}" alt="{{ $row->book->title }} cover" class="w-full h-full object-cover" loading="lazy">
                            @else
                                <span class="w-full h-full flex items-center justify-center text-center font-body-sm text-body-sm text-on-surface-variant p-2">{{ $row->book->title }}</span>
                            @endif
                            <span class="absolute top-1 left-1 px-1.5 py-0.5 rounded-full bg-inverse-surface/80 text-inverse-on-surface font-label-sm text-[10px] uppercase font-bold tracking-wider backdrop-blur-sm">eBook</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider text-primary">Digital eBook</span>
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface truncate">
                                        <a href="{{ route('books.show', $row->book->slug) }}" class="hover:underline">{{ $row->book->title }}</a>
                                    </h2>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-1 mb-3">
                                <span class="inline-flex items-center gap-1 text-on-surface-variant font-body-sm text-body-sm">
                                    <span class="material-symbols-outlined text-[16px] text-tertiary">picture_as_pdf</span>
                                    PDF format
                                </span>
                                <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                <span class="text-on-surface-variant font-body-sm text-body-sm">Instant Download</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 pt-2">
                                <div class="flex items-baseline gap-2">
                                    <span class="font-headline-md text-headline-md text-on-surface">{{ $row->price_display }}</span>
                                    @if($row->price_regular_display)
                                        <span class="book-card-oldprice font-body-sm text-body-sm">{{ $row->price_regular_display }}</span>
                                    @endif
                                    @if($row->price_quote['discount_percent'] ?? null)
                                        <span class="font-label-sm text-label-sm text-primary font-semibold px-2 py-0.5 rounded-full bg-primary-fixed/50">{{ $row->price_quote['discount_percent'] }}% OFF</span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('cart.destroy', $row) }}"
                                      onsubmit="return confirm('Remove this ebook from your cart?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-body-sm text-body-sm text-on-surface-variant hover:text-error transition-colors flex items-center gap-1" type="submit">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                        <span>Remove</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    {{-- Trust chips --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-surface-container-lowest shadow-sm">
                            <span class="material-symbols-outlined text-tertiary text-[24px]">bolt</span>
                            <div class="min-w-0">
                                <p class="font-label-md text-label-md font-semibold text-on-surface">Zero Wait</p>
                                <p class="font-body-sm text-[12px] text-on-surface-variant truncate">Instant cloud access</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-surface-container-lowest shadow-sm">
                            <span class="material-symbols-outlined text-tertiary text-[24px]">devices</span>
                            <div class="min-w-0">
                                <p class="font-label-md text-label-md font-semibold text-on-surface">Any Device</p>
                                <p class="font-body-sm text-[12px] text-on-surface-variant truncate">Kindle, phone, or laptop</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-surface-container-lowest shadow-sm">
                            <span class="material-symbols-outlined text-tertiary text-[24px]">all_inclusive</span>
                            <div class="min-w-0">
                                <p class="font-label-md text-label-md font-semibold text-on-surface">Lifetime Link</p>
                                <p class="font-body-sm text-[12px] text-on-surface-variant truncate">Re-download anytime</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="lg:col-span-5 flex flex-col gap-4 lg:sticky lg:top-24">
                    <div class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-7 shadow-sm">
                        <h2 class="font-headline-md text-headline-md text-on-surface mb-6">Order Summary</h2>

                        <div class="space-y-3.5 pb-6 border-b border-surface-container-high">
                            @foreach($summary['lines'] as $line)
                            <div class="flex items-center justify-between font-body-md text-body-md text-on-surface-variant">
                                <span class="truncate mr-3">{{ $line['title'] }}</span>
                                <span class="font-medium text-on-surface shrink-0">{{ $line['display'] }}</span>
                            </div>
                            @endforeach
                        </div>

                        <div class="py-4 flex items-baseline justify-between">
                            <span class="font-headline-sm text-headline-sm text-on-surface">Total Amount</span>
                            <div class="text-right">
                                <span class="font-display-lg text-[32px] leading-tight text-on-surface font-bold">{{ $summary['display_total'] }}</span>
                            </div>
                        </div>

                        {{-- Coupon --}}
                        @auth
                            @if($couponsEnabled && settings_enabled('features.checkout', true) && Route::has('checkout.coupon.apply'))
                            <div class="mt-4 mb-6">
                                @if($couponCode)
                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-low px-4 py-3">
                                        <p class="font-body-sm text-body-sm text-on-surface">
                                            <span class="inline-flex items-center gap-1.5 font-label-sm text-label-sm text-primary font-medium">
                                                <span class="material-symbols-outlined text-[18px]">verified</span>
                                                Coupon <strong class="font-semibold uppercase tracking-wider">{{ $couponCode }}</strong>
                                            </span>
                                            <span class="text-on-surface-variant"> applied</span>
                                        </p>
                                        <form method="POST" action="{{ route('checkout.coupon.remove') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-secondary underline hover:text-on-surface">Remove</button>
                                        </form>
                                    </div>
                                @else
                                    <label class="block font-label-sm text-label-sm uppercase font-semibold tracking-wider text-on-surface-variant mb-2" for="coupon-code">Have a promo code?</label>
                                    <form method="POST" action="{{ route('checkout.coupon.apply') }}" class="flex gap-2">
                                        @csrf
                                        <div class="relative flex-1">
                                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[18px]">sell</span>
                                            <input class="w-full h-11 pl-9 pr-3 rounded-full bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-on-surface-variant/50 focus:outline-none focus:ring-2 focus:ring-primary-container" id="coupon-code" name="code" value="{{ old('code') }}" placeholder="Enter coupon" type="text" aria-label="Coupon code">
                                        </div>
                                        <button class="h-11 px-5 rounded-full bg-surface-container text-on-surface hover:bg-secondary hover:text-on-secondary font-label-md text-label-md font-semibold transition-colors" type="submit">
                                            Apply
                                        </button>
                                    </form>
                                    @if($errors->has('coupon'))
                                        <p class="font-body-sm text-body-sm text-error mt-2" role="alert">{{ $errors->first('coupon') }}</p>
                                    @endif
                                @endif
                            </div>
                            @endif
                        @endauth

                        <div class="mt-4">
                            @guest
                                <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 h-12 rounded-full bg-primary-container text-on-primary font-label-md text-body-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-md hover:shadow-lg">
                                    Log in to check out
                                </a>
                                <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 text-center">You need an account before you can pay.</p>
                            @else
                                @if(settings_enabled('features.checkout', true) && Route::has('checkout'))
                                    <a href="{{ route('checkout') }}" class="w-full flex items-center justify-center gap-2 h-12 rounded-full bg-primary-container text-on-primary font-label-md text-body-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-md hover:shadow-lg">
                                        <span>Proceed to Checkout</span>
                                        <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                                        <span>{{ $summary['display_total'] }}</span>
                                        <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                                    </a>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 text-center">
                                        @if($summary['currency'] === 'INR')
                                            Charged in Indian rupees.
                                        @else
                                            Charged in US dollars.
                                        @endif
                                        All sales are final — <a href="{{ route('no-refund') }}" class="underline">no-refund policy</a>.
                                    </p>
                                @else
                                    <div class="rounded-DEFAULT bg-surface-container-low px-4 py-3 font-body-sm text-body-sm text-on-surface" role="status">Online checkout is being set up. Your cart is saved and ready.</div>
                                @endif
                            @endguest
                        </div>

                        <div class="mt-4 text-center">
                            <p class="font-body-sm text-body-sm text-on-surface-variant flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-tertiary">cloud_done</span>
                                Digital instant download. No shipping charges.
                            </p>
                        </div>

                        <div class="mt-6 pt-5 bg-surface-container-low -mx-6 -mb-6 sm:-mx-7 sm:-mb-7 px-6 py-4 rounded-b-DEFAULT flex flex-col sm:flex-row items-center justify-between gap-3 text-on-surface-variant">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px] text-secondary">lock</span>
                                <span class="font-label-sm text-label-sm font-medium">256-bit SSL Encrypted</span>
                            </div>
                            <div class="flex items-center gap-1.5 font-label-sm text-label-sm">
                                <span>Powered by</span>
                                <span class="font-semibold text-on-surface tracking-tight bg-surface-container-lowest px-2 py-0.5 rounded-full shadow-xs">Razorpay</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface-container-lowest rounded-DEFAULT p-5 shadow-sm flex items-start gap-3.5">
                        <span class="material-symbols-outlined text-primary text-[22px] flex-shrink-0 mt-0.5">verified_user</span>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">
                            <p class="font-label-md text-label-md font-semibold text-on-surface mb-0.5">MyEbookBazaar Reader Promise</p>
                            <p>Every purchase includes the DRM-free optimized mobile PDF delivered to your registered email in seconds.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
