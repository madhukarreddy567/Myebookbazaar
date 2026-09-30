@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Order ' . $order->order_number . ' — MyEbookBazaar')

@php
$gstRateLabel = \App\Services\OrderService::gstRateLabel((float) $order->gst_rate);
$capturedPayment = $order->payments->first(fn ($p) => $p->isCaptured() && $p->razorpay_payment_id);
$paymentMethodLabel = $order->payment_method === 'manual' ? 'Manual' : ($order->payment_method === 'coupon' ? 'Coupon (free)' : 'Razorpay');
$orderDate = $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A').' IST';
$siteAddress = trim((string) settings('site.address', ''));
$price = app(\App\Services\PriceService::class);
@endphp

@section('content')
<div class="st-page w-full py-8 md:py-12 px-4 sm:px-6">
<div class="max-w-[840px] mx-auto">

    {{-- Top action bar --}}
    <aside aria-label="Order quick actions" class="no-print flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 mb-6">
        <a class="inline-flex items-center gap-2 font-label-md text-label-md text-on-surface-variant hover:text-on-surface transition-colors py-2 px-1" href="{{ route('orders.index') }}">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to My Orders</span>
        </a>
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <button class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-full bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-medium transition-all shadow-sm" onclick="window.print()" type="button">
                <span class="material-symbols-outlined text-[18px]">print</span>
                <span>Print / Save PDF</span>
            </button>
            @if($order->isPaid())
            <a class="inline-flex items-center justify-center gap-2 h-10 px-5 rounded-full bg-primary-container hover:bg-primary text-on-primary font-label-md text-label-md font-semibold transition-all active:scale-[0.99] shadow-sm" href="{{ route('library') }}">
                <span class="material-symbols-outlined text-[18px]">download_for_offline</span>
                <span>Go to My Library</span>
            </a>
            @endif
        </div>
    </aside>

    @if(session('status'))
        <div class="no-print rounded-DEFAULT bg-surface-container-lowest border border-outline-variant/40 px-4 py-3 font-body-sm text-body-sm text-on-surface mb-6" role="status">{{ session('status') }}</div>
    @endif

    {{-- Invoice canvas --}}
    <article class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-10 md:p-12 shadow-sm space-y-10 text-on-surface relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-secondary-container/40 rounded-full blur-3xl pointer-events-none"></div>

        {{-- 1. Invoice header --}}
        <section class="relative z-10 flex flex-col md:flex-row items-start justify-between gap-8 pb-8 bg-surface-container-low/40 rounded-DEFAULT p-6">
            <div class="space-y-3 max-w-sm">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-[28px]">auto_stories</span>
                    <span class="font-headline-sm text-headline-sm text-on-surface font-bold tracking-tight">{{ $siteName }}</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    Simple, practical ebooks for everyday wellness and cooking.
                </p>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-surface-container-highest text-on-surface-variant font-label-sm text-label-sm">
                    <span class="material-symbols-outlined text-[14px] text-tertiary">verified_user</span>
                    <span>Digital Tax Invoice</span>
                </div>
            </div>
            <div class="w-full md:w-auto md:text-right flex flex-col items-start md:items-end space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-label-sm text-label-sm font-semibold tracking-wider uppercase
                    @if($order->isPaid()) bg-secondary-container text-on-secondary-container
                    @elseif($order->status === 'pending') bg-primary-fixed text-on-primary-fixed-variant
                    @else bg-surface-container text-on-surface-variant @endif">
                    <span class="w-2 h-2 rounded-full
                        @if($order->isPaid()) bg-tertiary
                        @elseif($order->status === 'pending') bg-primary-container
                        @else bg-on-surface-variant @endif"></span>
                    {{ $order->statusLabel() }}
                </div>
                <div class="font-headline-md text-headline-md font-bold text-on-surface tracking-tight mt-1">
                    #{{ $order->order_number }}
                </div>
                <div class="font-body-sm text-body-sm text-on-surface-variant space-y-0.5">
                    <p><span class="font-label-md font-semibold text-on-surface">Order ID:</span> #{{ $order->order_number }}</p>
                    <p><span class="font-label-md font-semibold text-on-surface">Date:</span> {{ $orderDate }}</p>
                    <p class="flex items-center gap-1 md:justify-end flex-wrap">
                        <span class="font-label-md font-semibold text-on-surface">Payment:</span>
                        <span>{{ $paymentMethodLabel }}</span>
                        @if($capturedPayment)
                        <span class="font-label-sm text-[11px] px-1.5 py-0.5 rounded bg-surface-container-high text-on-secondary-container font-mono">{{ $capturedPayment->razorpay_payment_id }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </section>

        {{-- 2. Billed to & sold by --}}
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
            <div class="bg-surface-container-lowest p-5 rounded-DEFAULT space-y-2 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[20px]">store</span>
                    <h2 class="font-label-md text-label-md font-semibold uppercase tracking-wider text-secondary">Sold By (Merchant)</h2>
                </div>
                <div class="font-headline-sm text-headline-sm font-bold text-on-surface">
                    {{ $siteName }}
                </div>
                <address class="not-italic font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    @if($siteAddress !== ''){{ $siteAddress }}<br/>@endif
                    @if($supportEmail)<span class="font-semibold text-on-surface">Support:</span> {{ $supportEmail }}@endif
                </address>
            </div>
            <div class="bg-surface-container-lowest p-5 rounded-DEFAULT space-y-2 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">account_circle</span>
                    <h2 class="font-label-md text-label-md font-semibold uppercase tracking-wider text-primary">Billed To (Customer)</h2>
                </div>
                <div class="font-headline-sm text-headline-sm font-bold text-on-surface">
                    {{ $order->user->name }}
                </div>
                <address class="not-italic font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    <span class="font-semibold text-on-surface">Email:</span> {{ $order->user->email }}<br/>
                    @if($order->user->country_name)<span class="font-semibold text-on-surface">Country:</span> {{ $order->user->country_name }}@endif
                </address>
            </div>
        </section>

        {{-- 3. Itemized purchases --}}
        <section class="space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left font-body-sm text-body-sm">
                    <thead>
                        <tr class="bg-surface-container text-on-surface font-label-md text-label-md">
                            <th class="py-3 px-4 rounded-l-DEFAULT" scope="col">Item Description</th>
                            <th class="py-3 px-3 text-center" scope="col">Qty</th>
                            <th class="py-3 px-3 text-right" scope="col">Rate</th>
                            <th class="py-3 px-4 text-right rounded-r-DEFAULT" scope="col">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-0">
                        @foreach($order->items as $item)
                        <tr class="bg-surface-container-lowest hover:bg-surface-container-low/30 transition-colors">
                            <td class="py-4 px-4 align-top">
                                <div class="flex items-center gap-3.5">
                                    <div class="relative w-12 h-16 rounded overflow-hidden shadow-sm shrink-0 bg-surface-container-high">
                                        @if($item->book && $item->book->displayCoverUrl())
                                        <img alt="Cover of {{ $item->title }}" class="w-full h-full object-cover" src="{{ $item->book->displayCoverUrl() }}" loading="lazy"/>
                                        @else
                                        <div class="w-full h-full flex items-center justify-center bg-secondary text-on-secondary">
                                            <span class="material-symbols-outlined text-[20px]">menu_book</span>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-headline-sm text-headline-sm font-semibold text-on-surface leading-tight">{{ $item->title }}</p>
                                        @if($item->book && $item->book->author)
                                        <p class="font-body-sm text-body-sm text-on-surface-variant">By {{ $item->book->author }}</p>
                                        @endif
                                        <span class="inline-flex items-center gap-1 mt-1 text-[11px] font-label-sm font-semibold px-2 py-0.5 rounded-full bg-secondary-container/60 text-secondary">
                                            Digital ebook · PDF
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-3 align-top text-center font-medium text-on-surface">{{ $item->quantity }}</td>
                            <td class="py-4 px-3 align-top text-right text-on-surface font-medium whitespace-nowrap">{{ $price->format((float) $item->unit_price, $item->currency) }}</td>
                            <td class="py-4 px-4 align-top text-right font-headline-sm text-headline-sm font-bold text-on-surface whitespace-nowrap">{{ $price->format((float) $item->line_total, $item->currency) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 4. Totals --}}
        <section class="flex flex-col sm:flex-row items-start justify-between gap-6 pt-4">
            <div class="sm:max-w-xs space-y-2">
                <h3 class="font-label-md text-label-md font-semibold text-on-surface">Payment Notes</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    @if($order->no_refund_accepted)
                    You accepted the no-refund policy at checkout — digital goods are final sale.
                    @else
                    Digital sale — final and non-refundable.
                    @endif
                    All amounts in {{ $order->currency === 'INR' ? 'Indian rupees' : 'US dollars' }}.
                </p>
                @if((float) $order->gst_rate > 0)
                <p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                    Includes GST ({{ $gstRateLabel }}%) of {{ $price->format((float) $order->gst_amount, $order->currency) }}.
                </p>
                @endif
            </div>
            <div class="w-full sm:w-80 bg-surface-container-low p-5 rounded-DEFAULT space-y-3">
                <div class="flex items-center justify-between font-body-sm text-body-sm text-on-surface-variant">
                    <span>Subtotal</span>
                    <span class="font-medium text-on-surface">{{ $price->format((float) $order->subtotal, $order->currency) }}</span>
                </div>
                @if((float) $order->discount_total > 0)
                <div class="flex items-center justify-between font-body-sm text-body-sm text-primary">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">sell</span>
                        Promotional Savings
                    </span>
                    <span class="font-semibold">−{{ $price->format((float) $order->discount_total, $order->currency) }}</span>
                </div>
                @endif
                @if((float) $order->gst_rate > 0)
                <div class="flex items-center justify-between font-body-sm text-body-sm text-on-surface-variant">
                    <span>GST ({{ $gstRateLabel }}%, included)</span>
                    <span>{{ $price->format((float) $order->gst_amount, $order->currency) }}</span>
                </div>
                @endif
                <div class="pt-3 flex items-center justify-between bg-surface-container-lowest p-3 rounded">
                    <div>
                        <span class="font-headline-sm text-headline-sm font-bold text-on-surface block">Total Paid</span>
                        <span class="font-label-sm text-[11px] text-on-surface-variant">Inclusive of all duties</span>
                    </div>
                    <div class="text-right">
                        <span class="font-headline-lg text-headline-lg font-bold text-primary block leading-none">{{ $price->format((float) $order->grand_total, $order->currency) }}</span>
                        <span class="font-label-sm text-label-sm text-secondary font-medium">{{ $paymentMethodLabel }}</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- 5. Digital access --}}
        @if($order->isPaid())
        <section class="no-print bg-secondary-container/30 p-6 sm:p-7 rounded-DEFAULT space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-tertiary text-[24px]">cloud_download</span>
                    <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface">Instant Ebook Access</h3>
                </div>
                <span class="font-label-sm text-label-sm text-secondary font-semibold">Direct links · 10-minute signed sessions</span>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Your {{ Str::plural('copy', $order->items->count()) }} of
                <strong class="text-on-surface">"{{ $order->items->first()?->title }}"</strong>
                @if($order->items->count() > 1)
                and {{ $order->items->count() - 1 }} more
                @endif
                are ready in your library with fresh signed download links.
            </p>
            <div>
                <a class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm" href="{{ route('library') }}">
                    <span class="material-symbols-outlined text-[18px]">download_for_offline</span>
                    <span>Open Downloads in Library</span>
                </a>
            </div>
        </section>
        @elseif($order->status === 'pending')
        <section class="no-print bg-primary-fixed/40 p-6 rounded-DEFAULT">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-primary text-[22px]">hourglass_top</span>
                <div>
                    <p class="font-label-md text-label-md font-semibold text-on-surface">Your payment is being confirmed.</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">If you just paid, this page will update shortly. Downloads unlock as soon as the payment is captured.</p>
                    <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-1.5 text-primary font-label-md text-label-md font-semibold hover:underline mt-3">
                        <span>Back to cart</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </section>
        @else
        <section class="no-print bg-error-container/40 p-6 rounded-DEFAULT">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-error text-[22px]">error</span>
                <div>
                    <p class="font-label-md text-label-md font-semibold text-on-surface">This payment did not go through.</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">No money was taken for this order.</p>
                    <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-1.5 text-primary font-label-md text-label-md font-semibold hover:underline mt-3">
                        <span>Try again</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </section>
        @endif

        {{-- 6. License & legal --}}
        <section class="pt-6 space-y-4 font-body-sm text-body-sm text-on-surface-variant">
            <div class="p-4 rounded-DEFAULT bg-surface-container-low flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-primary text-[22px] shrink-0 mt-0.5">verified</span>
                    <div>
                        <p class="font-label-md text-label-md font-semibold text-on-surface">Personal Single-User Digital License</p>
                        <p class="text-[13px] leading-relaxed text-on-surface-variant">
                            This publication is uniquely registered and licensed to <strong class="text-on-surface">{{ $order->user->email }}</strong>.
                            Resale, public redistribution, web hosting, or file-sharing is prohibited under the Indian Copyright Act, 1957.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-2 text-[12px] text-on-surface-variant/80">
                @if($capturedPayment)
                <div class="space-y-0.5">
                    <p>Razorpay payment ID:</p>
                    <p class="font-mono text-[11px] bg-surface-container px-2 py-0.5 rounded text-on-surface break-all select-all">{{ $capturedPayment->razorpay_payment_id }}</p>
                </div>
                @endif
                <div class="sm:text-right font-label-sm sm:ml-auto">
                    <p class="font-medium text-on-surface">Computer generated digital tax invoice.</p>
                    <p>No physical signature required.</p>
                </div>
            </div>
        </section>
    </article>

    {{-- Support strip --}}
    <footer class="no-print mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 p-5 rounded-DEFAULT bg-surface-container text-on-surface">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-tertiary text-[24px]">support_agent</span>
            <div>
                <p class="font-label-md text-label-md font-semibold text-on-surface">Trouble downloading or viewing your ebook?</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Our support desk replies within 2 working days.</p>
            </div>
        </div>
        <a class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-surface-container-lowest hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-medium transition-colors shadow-sm" href="{{ route('support.create') }}">
            <span class="material-symbols-outlined text-[18px]">mail</span>
            <span>Contact Support</span>
        </a>
    </footer>
</div>
</div>

<style>
@media print {
    .no-print, header, footer, nav { display: none !important; }
    body { background: #fff; }
}
</style>
@endsection
