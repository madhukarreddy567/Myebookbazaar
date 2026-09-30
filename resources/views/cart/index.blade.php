@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Your cart — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Your cart']]" />
    <h1 class="st-h text-[34px] md:text-[42px] mt-3">
        Your cart
        @if($items->isNotEmpty())
            <span class="st-badge-free align-middle ml-1">{{ $items->count() }} {{ Str::plural('item', $items->count()) }}</span>
        @endif
    </h1>

    @if(session('status'))
        <x-ui.alert type="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if($items->isEmpty())
        <div class="st-card st-empty mt-5">
            <x-ui.empty-state icon="cart" title="Your cart is empty"
                text="Browse ebooks and add your first title."
                :action-url="route('books')" action-label="Browse ebooks" />
        </div>
    @else
        <div class="grid lg:grid-cols-[1.6fr_1fr] gap-5 mt-5 items-start">
            {{-- Items --}}
            <div class="flex flex-col gap-3">
                @foreach($items as $row)
                @php($rowCover = $row->book->coverUrl() ?: (str_contains($row->book->slug, 'fit-at-home') ? asset('images/stitch/book-mockup-3d.jpg') : null))
                <div class="st-card p-4 flex gap-4 items-center">
                    <a href="{{ route('books.show', $row->book->slug) }}" class="w-16 shrink-0" tabindex="-1" aria-hidden="true">
                        @if($rowCover)
                            <img src="{{ $rowCover }}" alt="" class="rounded-lg w-full h-auto" loading="lazy">
                        @else
                            <div class="book-cover-placeholder aspect-[3/4] rounded-lg" role="img" aria-label="Cover coming soon">
                                <span class="text-xs">{{ $row->book->title }}</span>
                            </div>
                        @endif
                    </a>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('books.show', $row->book->slug) }}" class="font-bold text-[17px] hover:underline line-clamp-2">{{ $row->book->title }}</a>
                        <p class="text-sm st-muted mt-0.5">Digital PDF ebook &bull; Lifetime access</p>
                        <form method="POST" action="{{ route('cart.destroy', $row) }}" class="mt-1.5"
                              onsubmit="return confirm('Remove this ebook from your cart?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-[#B3372F] underline underline-offset-2">Remove</button>
                        </form>
                    </div>
                    <p class="text-xl shrink-0 font-extrabold text-right text-[#0B3D4A]">
                        {{ $row->price_display }}
                        @if($row->price_regular_display)
                            <span class="book-card-oldprice block text-sm font-semibold">{{ $row->price_regular_display }}</span>
                        @endif
                    </p>
                </div>
                @endforeach
                <a href="{{ route('books') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-[#0B3D4A] hover:underline mt-1">
                    <x-ui.icon name="arrow-right" class="w-4 h-4 rotate-180" /> Continue browsing
                </a>
            </div>

            {{-- Summary --}}
            <div class="st-card p-5 lg:sticky lg:top-24">
                <h2 class="st-h text-[22px]">Summary</h2>

                {{-- Coupon: input is always empty; never pre-filled --}}
                @auth
                    @if($couponsEnabled && settings_enabled('features.checkout', true) && Route::has('checkout.coupon.apply'))
                    <div class="mt-4">
                        @if($couponCode)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-[#E4E8F2] bg-[#F6F7FD] px-4 py-3">
                                <p class="text-sm">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider bg-[#0B3D4A] text-white rounded-md px-2 py-1">Coupon</span>
                                    <strong class="ml-1">{{ $couponCode }}</strong>
                                    <span class="st-muted">applied</span>
                                </p>
                                <form method="POST" action="{{ route('checkout.coupon.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-[#0B3D4A] underline hover:text-[#101E28]">Remove</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('checkout.coupon.apply') }}" class="flex gap-2">
                                @csrf
                                <x-ui.input name="code" placeholder="Coupon code" :value="old('code')" :error="$errors->first('coupon')" class="flex-1" aria-label="Coupon code" />
                                <button type="submit" class="st-btn st-btn-teal !min-h-[48px] !px-5">Apply</button>
                            </form>
                            @if($errors->has('coupon'))
                                <p class="st-field-error mt-1" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('coupon') }}</span></p>
                            @endif
                        @endif
                    </div>
                    @endif
                @endauth

                <dl class="mt-4 space-y-2 text-[15px]">
                    @foreach($summary['lines'] as $line)
                    <div class="flex justify-between gap-3">
                        <dt class="st-muted line-clamp-1">{{ $line['title'] }}</dt>
                        <dd class="shrink-0 font-semibold">{{ $line['display'] }}</dd>
                    </div>
                    @endforeach
                </dl>
                <div class="border-t border-[#E4E8F2] mt-4 pt-4 flex justify-between items-baseline">
                    <span class="font-bold">Total</span>
                    <span class="st-h text-[30px]">{{ $summary['display_total'] }}</span>
                </div>
                <p class="text-xs st-muted mt-1.5">
                    @if($summary['currency'] === 'INR')
                        Charged in Indian rupees.
                    @else
                        Charged in US dollars.
                    @endif
                    All sales are final — <a href="{{ route('no-refund') }}" class="underline">no-refund policy</a>.
                </p>

                <div class="mt-5">
                    @guest
                        <a href="{{ route('login') }}" class="st-btn st-btn-amber w-full !min-h-[56px] !text-[16px]">Log in to check out</a>
                        <p class="text-sm st-muted mt-2 text-center">You need an account before you can pay.</p>
                    @else
                        @if(settings_enabled('features.checkout', true) && Route::has('checkout'))
                            <a href="{{ route('checkout') }}" class="st-btn st-btn-amber w-full !min-h-[56px] !text-[16px] uppercase tracking-wide">Proceed to checkout</a>
                            <p class="flex items-center justify-center gap-1.5 mt-2.5 text-xs st-muted">
                                <x-ui.icon name="lock" class="w-4 h-4" /> Secure checkout &bull; Instant access after payment
                            </p>
                        @else
                            <x-ui.alert type="info">Online checkout is being set up. Your cart is saved and ready.</x-ui.alert>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
