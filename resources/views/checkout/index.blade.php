@extends('layouts.app')

@section('title', 'Checkout — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-8" x-data="checkout()">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Your cart', 'url' => route('cart.index')], ['label' => 'Checkout']]" />

    {{-- Step progress --}}
    <div class="mt-4">
        <div class="flex items-center justify-between mb-2 gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-teal-900 text-white text-xs font-bold shrink-0">2</span>
                <span class="text-xs font-bold uppercase tracking-wider truncate">Step 2 of 2: Payment &amp; Delivery</span>
            </div>
            <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 text-teal-900 text-[11px] font-bold px-2.5 py-1 shrink-0">
                <x-ui.icon name="lock" class="w-3.5 h-3.5" /> 256-Bit SSL
            </span>
        </div>
        <div class="h-1.5 rounded-full overflow-hidden flex bg-teal-100" aria-hidden="true">
            <div class="w-1/2 bg-tint-teal-dark"></div>
            <div class="w-1/2 bg-saffron-500"></div>
        </div>
    </div>

    @if(session('combo_warning'))
    <x-ui.alert type="warning" class="mt-4">{{ session('combo_warning') }}</x-ui.alert>
    @endif

    <div class="grid lg:grid-cols-[1.6fr_1fr] gap-5 mt-5 items-start">
        <div class="space-y-4 min-w-0">
            {{-- Order summary --}}
            <x-ui.card class="p-5">
                <h2 class="display-sm text-teal-900 uppercase">Order summary</h2>
                <div class="mt-4 space-y-5">
                    @foreach($items as $row)
                    <div class="flex gap-3.5 items-start">
                        <span class="relative w-20 shrink-0">
                            @if($row->book->coverUrl())
                                <img src="{{ $row->book->coverUrl() }}" alt="" class="rounded-lg w-full h-auto shadow-md" loading="lazy">
                            @elseif(str_contains($row->book->slug, 'fit-at-home'))
                                <img src="{{ asset('images/stitch/book-mockup-3d.jpg') }}" alt="" class="rounded-lg w-full h-auto shadow-md" loading="lazy">
                            @else
                                <span class="book-cover-placeholder aspect-[3/4] rounded-lg !p-2" role="img" aria-label="Cover coming soon"><span class="!text-sm">{{ $row->book->title }}</span></span>
                            @endif
                            <span class="absolute -bottom-1.5 -right-1.5 inline-flex items-center gap-0.5 rounded bg-teal-900 text-white text-[10px] font-bold px-1.5 py-0.5 shadow">
                                <x-ui.icon name="file-text" class="w-3 h-3" /> PDF
                            </span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="inline-flex items-center gap-1 rounded bg-teal-100 text-teal-900 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 mb-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-saffron-500" aria-hidden="true"></span> Instant access guide
                            </span>
                            <span class="display-sm text-teal-900 uppercase leading-tight block line-clamp-2">{{ $row->book->title }}</span>
                            <span class="text-sm text-mutedtext block mt-0.5">Digital PDF ebook{{ $row->book->author ? ' · by '.$row->book->author : '' }}</span>
                            <span class="price font-display text-2xl font-extrabold text-saffron-600 block mt-1">{{ $row->price_display }}</span>
                        </span>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 rounded-xl bg-tint-teal p-3.5 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-mutedtext">Subtotal</span>
                        <span class="price font-bold">{{ $summary['display_subtotal'] }}</span>
                    </div>
                    @if($quote['discount'] > 0)
                    <div class="flex justify-between text-saffron-600">
                        <span class="inline-flex items-center gap-1 font-medium">
                            <x-ui.icon name="tag" class="w-4 h-4" /> Coupon discount ({{ $quote['coupon']->code }})
                        </span>
                        <span class="price font-bold">−{{ $summary['display_discount'] }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-mutedtext">Delivery fee (instant cloud link)</span>
                        <span class="font-bold text-teal-900 uppercase text-xs tracking-wider">Free</span>
                    </div>
                    <div class="flex justify-between items-center pt-2.5 mt-1 border-t border-line">
                        <span class="font-bold text-lg">Final payable</span>
                        <span class="price font-display text-3xl font-extrabold text-saffron-600">{{ $summary['display_total'] }}</span>
                    </div>
                </div>
                <p class="mt-2.5 text-sm text-mutedtext">
                    Paying in {{ $summary['currency'] === 'INR' ? 'Indian rupees (₹)' : 'US dollars ($)' }}.
                    The amount is calculated on our server and cannot be changed.
                </p>
            </x-ui.card>

            {{-- Coupon (Phase 4): server-side only, plain form reload.
                 Shown even when the gateway is down — a 100% coupon
                 completes the order without any gateway involvement. --}}
            @if($couponsEnabled)
            <x-ui.card class="p-5">
                <h2 class="font-bold text-teal-900">Discount coupon</h2>
                @if($quote['coupon'])
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-line bg-tint-teal px-4 py-3">
                        <p class="text-sm">
                            <x-ui.badge>Coupon</x-ui.badge>
                            <strong class="ml-1 uppercase tracking-wider">{{ $quote['coupon']->code }}</strong>
                            <span class="text-mutedtext">applied — you save {{ $summary['display_discount'] }}</span>
                        </p>
                        <form method="POST" action="{{ route('checkout.coupon.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-teal-700 underline hover:text-inktext">Remove</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('checkout.coupon.apply') }}" class="mt-3 flex gap-2">
                        @csrf
                        <x-ui.input name="code" placeholder="Coupon code" :value="old('code')" :error="$errors->first('coupon')" class="flex-1" aria-label="Coupon code" />
                        <x-ui.button type="submit" variant="secondary">Apply</x-ui.button>
                    </form>
                    @if($errors->has('coupon') && !$errors->has('code'))
                        <p class="field-error mt-1" role="alert"><x-ui.icon name="alert" class="w-4 h-4" /><span>{{ $errors->first('coupon') }}</span></p>
                    @endif
                    @if(session('coupon_notice'))
                        <p class="mt-2 text-sm text-mutedtext">{{ session('coupon_notice') }}</p>
                    @endif
                @endif
            </x-ui.card>
            @endif

            {{-- Buyer details (read-only: the signed-in customer) --}}
            <x-ui.card class="p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-teal-900 inline-flex items-center gap-1.5">
                        <x-ui.icon name="user" class="w-5 h-5" /> Buyer details
                    </h2>
                    <span class="text-[11px] text-mutedtext">* Required</span>
                </div>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 rounded-lg bg-tint-teal px-3.5 py-2.5">
                        <dt class="text-mutedtext">Full name</dt>
                        <dd class="font-bold">{{ auth()->user()->name }}</dd>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-0.5 rounded-lg bg-tint-teal px-3.5 py-2.5">
                        <dt class="text-mutedtext">Email address</dt>
                        <dd class="font-bold break-all">{{ auth()->user()->email }}</dd>
                    </div>
                </dl>
                <p class="mt-2.5 text-xs text-mutedtext flex items-start gap-1.5">
                    <x-ui.icon name="mail" class="w-4 h-4 shrink-0 mt-0.5 text-teal-700" />
                    <span>Your instant PDF download link will be delivered directly to this email inbox.</span>
                </p>
            </x-ui.card>
        </div>

        {{-- Payment --}}
        <x-ui.card class="p-5 lg:sticky lg:top-24">
            <h2 class="display-sm text-teal-900 uppercase">Payment</h2>

            @if($gatewayError)
                <x-ui.alert type="warning" class="mt-4">{{ $gatewayError }}</x-ui.alert>
                <x-ui.button variant="secondary" href="{{ route('cart.index') }}" class="w-full mt-4">Back to cart</x-ui.button>
            @else
                @if($combo)
                <div class="mt-4 rounded-xl border border-line p-4 bg-tint-teal">
                    <p class="text-sm"><strong>Combo:</strong> {{ $combo->title }} — all {{ $combo->books()->count() }} ebooks included at one price.</p>
                </div>
                @endif

                <div class="mt-4 flex items-start gap-3 rounded-xl bg-saffron-100 border border-tint-saffron p-4">
                    <input type="checkbox" id="no-refund" x-model="accepted" class="mt-1 w-5 h-5 accent-saffron-500 shrink-0" required>
                    <label for="no-refund" class="text-sm leading-relaxed">
                        <strong class="text-saffron-600">Digital delivery agreement:</strong>
                        I understand that digital ebook sales are <strong>final and non-refundable</strong>.
                        I have read the <a href="{{ route('no-refund') }}" target="_blank" class="underline font-medium">no-refund policy</a>.
                    </label>
                </div>
                <p x-show="error && !accepted" x-cloak class="mt-2 text-sm text-danger">Please accept the no-refund policy to continue.</p>

                <x-turnstile form="checkout" />

                <button type="button" @click="pay()" :disabled="busy"
                        class="mt-4 w-full h-14 rounded-xl bg-saffron-500 hover:bg-saffron-600 disabled:opacity-70 text-white font-extrabold uppercase tracking-wider text-lg shadow-lg shadow-glow-saffron inline-flex items-center justify-center gap-2 transition-all active:scale-[0.99] btn-glow">
                    <x-ui.icon name="lock" class="w-5 h-5" />
                    <span x-show="!busy">{{ $quote['free'] ? 'Complete order — free' : 'Pay '.$summary['display_total'].' • Get instant PDF' }}</span>
                    <span x-show="busy" x-cloak>Processing…</span>
                </button>

                <div x-show="error" x-cloak class="mt-3">
                    <x-ui.alert type="danger"><span x-text="error"></span></x-ui.alert>
                </div>

                @if(!$quote['free'])
                <p class="mt-4 text-xs text-mutedtext flex items-center justify-center gap-1.5 text-center">
                    <x-ui.icon name="shield" class="w-4 h-4 shrink-0 text-teal-700" />
                    <span>UPI • Cards • NetBanking via Razorpay • 256-Bit SSL Encrypted</span>
                </p>
                <div class="mt-2.5 flex flex-wrap items-center justify-center gap-1.5" aria-hidden="true">
                    @foreach(['GPay','PhonePe','Paytm','UPI','Visa','Mastercard','NetBanking'] as $chip)
                    <span class="px-2 py-1 rounded bg-tint-teal-strong text-[10px] font-bold tracking-wide text-teal-900 uppercase">{{ $chip }}</span>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-mutedtext flex items-center gap-1.5">
                    <x-ui.icon name="lock" class="w-4 h-4 shrink-0" /> Secure payment via Razorpay. We never see your card details.
                </p>
                @else
                <p class="mt-4 text-xs text-mutedtext flex items-center gap-1.5">
                    <x-ui.icon name="check" class="w-4 h-4 shrink-0" /> Your coupon covers the full amount — no payment needed.
                </p>
                @endif
            @endif
        </x-ui.card>
    </div>

    {{-- Trust strip --}}
    <div class="mt-6 grid grid-cols-3 gap-2 text-center">
        <div class="flex flex-col items-center gap-1 rounded-xl bg-tint-teal p-3">
            <img src="{{ asset('images/stitch/icon-instant-access.png') }}" alt="" class="w-9 h-9" loading="lazy">
            <span class="text-[10px] font-bold uppercase tracking-wider">Instant download</span>
            <span class="text-[10px] text-mutedtext leading-tight">Ready in seconds</span>
        </div>
        <div class="flex flex-col items-center gap-1 rounded-xl bg-tint-teal p-3">
            <img src="{{ asset('images/stitch/icon-secure-checkout.png') }}" alt="" class="w-9 h-9" loading="lazy">
            <span class="text-[10px] font-bold uppercase tracking-wider">Safe &amp; secure</span>
            <span class="text-[10px] text-mutedtext leading-tight">Razorpay protected</span>
        </div>
        <div class="flex flex-col items-center gap-1 rounded-xl bg-tint-teal p-3">
            <img src="{{ asset('images/stitch/icon-shield.png') }}" alt="" class="w-9 h-9" loading="lazy">
            <span class="text-[10px] font-bold uppercase tracking-wider">Lifetime copy</span>
            <span class="text-[10px] text-mutedtext leading-tight">Stored in your email</span>
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
                    await fetch(data.failure_url, {
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
                } finally {
                    self.busy = false;
                    self.error = 'The payment failed. No money was taken — you can try again.';
                }
            });
            rzp.open();
        },
    };
}
</script>
@endsection
