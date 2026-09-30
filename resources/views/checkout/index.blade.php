@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Checkout — MyEbookBazaar')

@section('content')
<div class="st-page w-full flex flex-col py-8 md:py-12 px-4 sm:px-6" x-data="checkout()">
    <div class="w-full max-w-[640px] mx-auto">

        {{-- Top row --}}
        <div class="mb-6 flex items-center justify-between">
            <a class="inline-flex items-center gap-1.5 font-label-md text-label-md text-secondary hover:text-on-surface transition-colors" href="{{ route('cart.index') }}">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Back to Cart</span>
            </a>
            <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-container-high text-secondary font-label-sm text-label-sm">
                <span class="material-symbols-outlined text-[15px]">verified_user</span>
                <span>Secure Session</span>
            </div>
        </div>

        @if(session('combo_warning'))
        <div class="rounded-DEFAULT bg-error-container/50 border border-error/30 px-4 py-3 font-body-sm text-body-sm text-on-error-container mb-6" role="alert">{{ session('combo_warning') }}</div>
        @endif

        <div class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 shadow-sm">
            <div class="pb-6">
                <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight">Checkout</h1>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Review your ebook order and receive instant digital access in seconds.</p>
            </div>

            {{-- Order summary preview --}}
            <section class="bg-surface-container-low rounded-DEFAULT p-4 sm:p-5 mb-6">
                <div class="flex flex-col gap-5">
                    @foreach($items as $row)
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-16 flex-shrink-0 rounded-sm overflow-hidden bg-surface-container shadow-sm">
                            @if($row->book->displayCoverUrl())
                                <img src="{{ $row->book->displayCoverUrl() }}" alt="{{ $row->book->title }} cover" class="w-full h-full object-cover" loading="lazy">
                            @else
                                <span class="w-full h-full flex items-center justify-center text-center font-body-sm text-[10px] text-on-surface-variant p-1">{{ $row->book->title }}</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h2 class="font-headline-sm text-headline-sm text-on-surface leading-snug line-clamp-1">{{ $row->book->title }}</h2>
                                    <p class="font-body-sm text-body-sm text-secondary font-medium mt-0.5">PDF format included{{ $row->book->author ? ' · by '.$row->book->author : '' }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ $row->price_display }}</span>
                                    @if($row->price_regular_display)
                                        <span class="book-card-oldprice block font-label-sm text-label-sm text-on-surface-variant">{{ $row->price_regular_display }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 pt-3 flex items-center justify-between font-label-sm text-label-sm text-on-surface-variant">
                                <span class="inline-flex items-center gap-1 text-primary font-medium">
                                    <span class="material-symbols-outlined text-[16px]">bolt</span>
                                    <span>Immediate direct download</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Server-rendered totals --}}
                <div class="mt-4 pt-4 border-t border-outline-variant/30 space-y-2">
                    <div class="flex justify-between font-body-sm text-body-sm text-on-surface-variant">
                        <span>Subtotal</span>
                        <span>{{ $summary['display_subtotal'] }}</span>
                    </div>
                    @if($quote['discount'] > 0)
                    <div class="flex justify-between font-body-sm text-body-sm text-primary font-medium">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">verified</span>
                            <span>Coupon discount ({{ $quote['coupon']->code }})</span>
                        </span>
                        <span>−{{ $summary['display_discount'] }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-body-sm text-body-sm text-on-surface-variant">
                        <span>Delivery fee (instant cloud link)</span>
                        <span class="text-on-surface font-medium uppercase text-xs tracking-wider">Free</span>
                    </div>
                    @if($summary['gst_rate'] > 0)
                    <div class="flex justify-between font-body-sm text-body-sm text-on-surface-variant">
                        <span>Includes GST ({{ $summary['gst_rate_label'] }}%)</span>
                        <span class="font-medium text-on-surface">{{ $summary['display_gst'] }}</span>
                    </div>
                    @endif
                    <div class="pt-2 flex justify-between items-baseline font-headline-sm text-headline-sm text-on-surface font-bold">
                        <span>Total Payable</span>
                        <span class="text-primary font-bold">{{ $summary['display_total'] }}</span>
                    </div>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-3">
                    Paying in {{ $summary['currency'] === 'INR' ? 'Indian rupees (₹)' : 'US dollars ($)' }}.
                    The amount is calculated on our server and cannot be changed.
                </p>
            </section>

            {{-- Coupon: server-side only, plain form reload.
                 Shown even when the gateway is down — a 100% coupon
                 completes the order without any gateway involvement. --}}
            @if($couponsEnabled)
            <section class="mb-6">
                <h2 class="font-label-md text-label-md font-semibold text-on-surface mb-1.5">Discount coupon</h2>
                @if($quote['coupon'])
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-low px-4 py-3">
                        <p class="font-body-sm text-body-sm text-on-surface">
                            <span class="inline-flex items-center gap-1.5 font-label-sm text-label-sm text-primary font-medium">
                                <span class="material-symbols-outlined text-[18px]">verified</span>
                                <span>Coupon <strong class="uppercase tracking-wider">{{ $quote['coupon']->code }}</strong></span>
                            </span>
                            <span class="text-on-surface-variant"> applied — you save {{ $summary['display_discount'] }}</span>
                        </p>
                        <form method="POST" action="{{ route('checkout.coupon.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-secondary underline hover:text-on-surface">Remove</button>
                        </form>
                    </div>
                @else
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
                    @if($errors->has('coupon') && !$errors->has('code'))
                        <p class="font-body-sm text-body-sm text-error mt-2" role="alert">{{ $errors->first('coupon') }}</p>
                    @endif
                    @if(session('coupon_notice'))
                        <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">{{ session('coupon_notice') }}</p>
                    @endif
                @endif
            </section>
            @endif

            {{-- Buyer details (read-only: the signed-in customer) --}}
            <section class="mb-6">
                <div class="flex items-center justify-between mb-1.5">
                    <h2 class="font-label-md text-label-md font-semibold text-on-surface inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px] text-secondary">person</span>
                        Buyer details
                    </h2>
                    <span class="font-label-sm text-label-sm text-on-surface-variant">* Required</span>
                </div>
                <div class="rounded-DEFAULT bg-surface-container-low px-4 py-3.5 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5">
                        <span class="font-body-sm text-body-sm text-on-surface-variant">Full name</span>
                        <span class="font-body-sm text-body-sm font-semibold text-on-surface">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5">
                        <span class="font-body-sm text-body-sm text-on-surface-variant">Email address</span>
                        <span class="font-body-sm text-body-sm font-semibold text-on-surface break-all">{{ auth()->user()->email }}</span>
                    </div>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[15px] text-secondary">info</span>
                    <span>Your personal download links &amp; payment invoice will be dispatched here.</span>
                </p>
            </section>

            {{-- Payment --}}
            <section>
                @if($gatewayError)
                    <div class="rounded-DEFAULT bg-error-container/50 border border-error/30 px-4 py-3 font-body-sm text-body-sm text-on-error-container" role="alert">{{ $gatewayError }}</div>
                    <a href="{{ route('cart.index') }}" class="mt-4 inline-flex items-center justify-center h-11 px-6 rounded-full bg-surface-container text-on-surface hover:bg-secondary hover:text-on-secondary font-label-md text-label-md font-semibold transition-colors w-full">Back to cart</a>
                @else
                    @if($combo)
                    <div class="mb-4 rounded-DEFAULT bg-surface-container-low p-4">
                        <p class="font-body-sm text-body-sm text-on-surface"><strong>Combo:</strong> {{ $combo->title }} — all {{ $combo->books()->count() }} ebooks included at one price.</p>
                    </div>
                    @endif

                    {{-- Required agreement --}}
                    <div class="pt-2">
                        <label class="flex items-start gap-3 cursor-pointer group select-none">
                            <input class="mt-1 h-4 w-4 rounded text-primary-container focus:ring-primary-container accent-primary-container cursor-pointer flex-shrink-0" id="no-refund" name="no_refund_accepted" type="checkbox" x-model="accepted">
                            <span class="font-body-sm text-body-sm text-on-surface group-hover:text-on-surface transition-colors leading-relaxed">
                                <strong class="text-primary">Digital delivery agreement:</strong>
                                I understand that digital ebook sales are <strong>final and non-refundable</strong>.
                                I have read the <a href="{{ route('no-refund') }}" target="_blank" class="underline font-medium">no-refund policy</a>.
                            </span>
                        </label>
                        <p x-show="error && !accepted" x-cloak class="mt-2 font-body-sm text-body-sm text-error">Please accept the no-refund policy to continue.</p>
                    </div>

                    <x-turnstile form="checkout" />

                    {{-- Pay button --}}
                    <div class="pt-3">
                        <button type="button" @click="pay()" :disabled="busy"
                                class="w-full h-12 min-h-[48px] rounded-full bg-primary-container text-on-primary font-label-md text-body-md font-bold hover:bg-primary transition-all active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer shadow-sm disabled:opacity-70 disabled:cursor-not-allowed">
                            <span class="material-symbols-outlined text-[20px]">download</span>
                            <span x-show="!busy">{{ $quote['free'] ? 'Complete order — free' : 'Pay '.$summary['display_total'].' & Download Instantly' }}</span>
                            <span x-show="busy" x-cloak>Processing…</span>
                        </button>
                    </div>

                    <div x-show="error" x-cloak class="mt-3">
                        <div class="rounded-DEFAULT bg-error-container/60 border border-error/30 px-4 py-3 font-body-sm text-body-sm text-on-error-container" role="alert"><span x-text="error"></span></div>
                    </div>

                    @if(!$quote['free'])
                    <div class="text-center pt-3 pb-1">
                        <p class="font-label-sm text-label-sm text-secondary font-medium flex items-center justify-center gap-1.5 flex-wrap">
                            <span class="material-symbols-outlined text-[16px] text-secondary">lock</span>
                            <span>UPI • Cards • NetBanking via Razorpay • 256-Bit SSL Encrypted</span>
                        </p>
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center justify-center gap-1.5" aria-hidden="true">
                        @foreach(['GPay','PhonePe','Paytm','UPI','Visa','Mastercard','NetBanking'] as $chip)
                        <span class="px-2 py-1 rounded bg-surface-container-low text-[10px] font-bold tracking-wide text-secondary uppercase">{{ $chip }}</span>
                        @endforeach
                    </div>
                    <p class="mt-3 font-body-sm text-body-sm text-on-surface-variant flex items-center justify-center gap-1.5 text-center">
                        <span class="material-symbols-outlined text-[16px] text-secondary">lock</span>
                        <span>Secure payment via Razorpay. We never see your card details.</span>
                    </p>
                    @else
                    <p class="mt-4 font-body-sm text-body-sm text-on-surface-variant flex items-center justify-center gap-1.5 text-center">
                        <span class="material-symbols-outlined text-[16px] text-tertiary">check</span>
                        <span>Your coupon covers the full amount — no payment needed.</span>
                    </p>
                    @endif
                @endif
            </section>
        </div>

        {{-- Trust badges --}}
        <div class="mt-6 grid grid-cols-3 gap-3 text-center">
            <div class="p-3 rounded-DEFAULT bg-surface-container-lowest/60">
                <span class="material-symbols-outlined text-secondary text-[22px] mb-1">offline_pin</span>
                <div class="font-label-sm text-label-sm text-on-surface font-semibold">Lifetime Access</div>
                <div class="font-body-sm text-[11px] text-on-surface-variant">Keep files forever</div>
            </div>
            <div class="p-3 rounded-DEFAULT bg-surface-container-lowest/60">
                <span class="material-symbols-outlined text-secondary text-[22px] mb-1">devices</span>
                <div class="font-label-sm text-label-sm text-on-surface font-semibold">Any Device</div>
                <div class="font-body-sm text-[11px] text-on-surface-variant">Kindle, Mobile &amp; PC</div>
            </div>
            <div class="p-3 rounded-DEFAULT bg-surface-container-lowest/60">
                <span class="material-symbols-outlined text-secondary text-[22px] mb-1">support_agent</span>
                <div class="font-label-sm text-label-sm text-on-surface font-semibold">Instant Help</div>
                <div class="font-body-sm text-[11px] text-on-surface-variant">Direct human support</div>
            </div>
        </div>
    </div>

    @if(!$gatewayError)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif
    <script>
    // Defined always: the payment card binds x-data="checkout()" even when the
    // gateway is down (coupon form stays visible so a 100% coupon can still be
    // applied). Razorpay is only invoked on the paid path.
    function checkout() {
        return {
            accepted: false,
            busy: false,
            error: '',
            async pay() {
                this.error = '';
                if (!this.accepted) { this.error = 'accept'; return; }
                this.busy = true;
                try {
                    const res = await fetch("{{ route('checkout') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            no_refund_accepted: 'yes',
                            cf_turnstile_response: document.querySelector('input[name="cf-turnstile-response"]')?.value || null,
                        }),
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Could not start the payment.');
                    // 100% coupon: the order is already paid — go straight to thank-you.
                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    this.openRazorpay(data);
                } catch (e) {
                    this.busy = false;
                    this.error = e.message;
                }
            },
            openRazorpay(data) {
                if (typeof Razorpay === 'undefined') {
                    this.busy = false;
                    this.error = 'The payment gateway is not available right now. Please try again later.';
                    return;
                }
                const self = this;
                const rzp = new Razorpay({
                    key: data.key,
                    amount: data.amount,
                    currency: data.currency,
                    name: data.name,
                    order_id: data.razorpay_order_id,
                    prefill: data.prefill,
                    theme: { color: '#0B3D4A' },
                    modal: { ondismiss: function () { self.busy = false; } },
                    handler: async function (resp) {
                        try {
                            const v = await fetch(data.verify_url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(resp),
                            });
                            const vd = await v.json();
                            if (!v.ok) throw new Error(vd.message || 'Verification failed.');
                            window.location.href = vd.redirect;
                        } catch (e) {
                            self.busy = false;
                            self.error = e.message;
                        }
                    },
                });
                rzp.on('payment.failed', async function (resp) {
                    try {
                        const r = await fetch(data.failure_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                razorpay_order_id: data.razorpay_order_id,
                                description: (resp.error && resp.error.description) || 'Payment failed at the gateway.',
                            }),
                        });
                        const rd = await r.json();
                        if (rd.redirect) {
                            window.location.href = rd.redirect;
                            return;
                        }
                    } catch (e) {
                        // Fall through to the inline error below.
                    }
                    self.busy = false;
                    self.error = 'The payment failed. No money was taken — you can try again.';
                });
                rzp.open();
            },
        };
    }
    </script>
</div>
@endsection
