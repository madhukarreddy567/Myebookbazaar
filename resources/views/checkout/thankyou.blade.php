@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Thank you — MyEbookBazaar')

@php
$paymentFailed = ($paymentFailed ?? false) || (bool) session('payment_failed');
$maskedEmail = preg_replace('/^(.).*(@.*)$/', '$1***$2', (string) $order->user->email);
$firstDownloadUrl = null;
foreach ($order->items as $it) {
    if ($it->book && $it->book->hasFile()) {
        $firstDownloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'download', now()->addMinutes(10), ['order' => $order->id, 'book' => $it->book_id]
        );
        break;
    }
}
$paidAt = $order->created_at ? $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '';
$symbol = $order->currency === 'INR' ? '₹' : '$';
$gstRateLabel = \App\Services\OrderService::gstRateLabel((float) $order->gst_rate).'%';
$amountPaid = $symbol.number_format((float) $order->grand_total, 2);
@endphp

@section('content')
<div class="st-page w-full py-12 sm:py-16 px-4 sm:px-6 flex flex-col items-center">
    <div class="w-full max-w-[700px] flex flex-col gap-6">

        @if($paymentFailed)
            {{-- ============ FAILURE STATE ============ --}}
            <section class="w-full bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-10 shadow-sm">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-error-container flex items-center justify-center text-error mb-5 shadow-sm">
                        <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;">error</span>
                    </div>
                    <span class="font-label-sm text-label-sm text-error uppercase tracking-wider font-bold mb-1">Transaction Incomplete</span>
                    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight font-bold mb-3">Payment Didn't Go Through</h1>
                    <div class="p-4 rounded-DEFAULT bg-error-container/40 max-w-lg mb-6 text-on-surface">
                        <p class="font-body-md text-body-md text-on-error-container leading-relaxed">
                            Don't worry — your cart is completely safe and your bank account has <strong class="font-semibold">NOT</strong> been debited twice.
                        </p>
                    </div>
                    <div class="inline-flex flex-wrap items-center justify-center gap-2 sm:gap-3 px-4 py-2 rounded-full bg-surface-container-low text-secondary font-label-sm text-label-sm mb-8">
                        <span class="font-semibold text-on-surface">Order {{ $order->order_number }}</span>
                        <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                        <span>Total Payable: {{ $amountPaid }} {{ $order->currency }}</span>
                        <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                        <span>Razorpay Secure Gateway</span>
                    </div>
                </div>

                <div class="w-full bg-surface-container-low rounded p-space-sm flex items-start gap-space-sm mb-8">
                    <span class="material-symbols-outlined text-primary text-[20px] shrink-0 mt-0.5">verified_user</span>
                    <p class="font-label-sm text-label-sm text-on-surface-variant leading-normal">
                        If any money was deducted by your bank, it is usually reversed automatically to your original account within
                        <span class="font-semibold text-primary">2–4 hours</span>.
                    </p>
                </div>

                {{-- Reserved items --}}
                <div class="p-4 rounded-DEFAULT bg-surface-container-low/70 flex flex-col gap-4 mb-8">
                    <p class="font-label-md text-label-md font-semibold text-on-surface">Your reserved items</p>
                    @foreach($order->items as $item)
                    <div class="flex items-center gap-4">
                        <div class="w-14 aspect-[2/3] rounded-sm overflow-hidden flex-shrink-0 shadow-sm bg-surface-container-high">
                            @if($item->book && $item->book->displayCoverUrl())
                                <img src="{{ $item->book->displayCoverUrl() }}" alt="{{ $item->title }} cover" class="w-full h-full object-cover" loading="lazy">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold truncate">{{ $item->title }}</h3>
                            <p class="font-body-sm text-body-sm text-secondary">Ready in cart for instant authorization</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Retry actions --}}
                <div class="flex flex-col gap-3">
                    <a href="{{ route('checkout') }}" class="w-full flex items-center justify-center gap-2 h-12 px-6 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">refresh</span>
                        <span>Try Payment Again ({{ $amountPaid }})</span>
                    </a>
                    <a href="{{ route('cart.index') }}" class="w-full flex items-center justify-center gap-2 h-12 px-6 rounded-full bg-surface-container text-secondary font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-all active:scale-[0.99]">
                        <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                        <span>Back to Cart</span>
                    </a>
                </div>

                <div class="mt-8 text-center pt-6 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-center gap-2 text-on-surface-variant font-body-sm text-body-sm">
                    <span>Was money deducted from your bank?</span>
                    <a class="inline-flex items-center gap-1 text-primary hover:text-on-primary-fixed-variant font-semibold" href="{{ route('contact') }}">
                        <span>Contact Support with Reference {{ $order->order_number }}</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </a>
                </div>
            </section>
        @else
            {{-- ============ SUCCESS STATE ============ --}}
            <section class="w-full bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-10 shadow-sm">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-surface-container-high flex items-center justify-center text-tertiary mb-5 shadow-sm">
                        <span class="material-symbols-outlined text-[36px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    </div>
                    <span class="font-label-sm text-label-sm text-tertiary uppercase tracking-wider font-bold mb-1">Transaction Confirmed</span>
                    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight font-bold mb-3">Payment Successful!</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant max-w-lg mb-6 leading-relaxed">
                        Thank you for your purchase{{ auth()->user() ? ', '.e(auth()->user()->name) : '' }}. Your ebook is ready to download and an archival copy has been dispatched to
                        <span class="font-medium text-on-surface">{{ $maskedEmail }}</span>.
                    </p>
                    <div class="inline-flex flex-wrap items-center justify-center gap-2 sm:gap-3 px-4 py-2 rounded-full bg-surface-container-low text-secondary font-label-sm text-label-sm">
                        <span class="font-semibold text-on-surface" id="order-ref">Order {{ $order->order_number }}</span>
                        <button type="button" id="copy-order" aria-label="Copy order reference"
                                class="w-6 h-6 inline-flex items-center justify-center rounded-full bg-surface-container-highest text-secondary hover:text-on-surface transition-colors">
                            <span class="material-symbols-outlined text-[14px]">content_copy</span>
                        </button>
                        <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                        <span>Paid {{ $amountPaid }} {{ $order->currency }} via Razorpay</span>
                        <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                        <span>{{ $paidAt }}</span>
                    </div>
                    @if((float) $order->gst_rate > 0)
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-2">Includes GST ({{ $gstRateLabel }}) of {{ $symbol }}{{ number_format((float) $order->gst_amount, 2) }}.</p>
                    @endif
                </div>

                {{-- Download card --}}
                <div class="mt-8 p-5 sm:p-6 rounded-DEFAULT bg-surface-container-low/70 flex flex-col gap-6">
                    @foreach($order->items as $item)
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                        <div class="w-20 aspect-[2/3] rounded-sm overflow-hidden flex-shrink-0 shadow-md bg-surface-container-high">
                            @if($item->book && $item->book->displayCoverUrl())
                                <img src="{{ $item->book->displayCoverUrl() }}" alt="{{ $item->title }} cover" class="w-full h-full object-cover" loading="lazy">
                            @endif
                        </div>
                        <div class="flex-1 text-center sm:text-left flex flex-col justify-center">
                            <div class="inline-flex items-center gap-1.5 self-center sm:self-start px-2.5 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-[11px] font-bold mb-2">
                                <span class="material-symbols-outlined text-[13px]" style="font-variation-settings: 'FILL' 1;">verified</span>
                                Verified Digital Edition
                            </div>
                            <h2 class="font-headline-md text-headline-md text-on-surface font-bold tracking-tight">{{ $item->title }}</h2>
                            @if($downloadsEnabled)
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
                                <span class="material-symbols-outlined text-[14px] text-tertiary inline-block align-middle">download</span>
                                {{ \App\Models\DownloadLog::usedCount($order->id, $item->book_id) }} of {{ $downloadLimit }} downloads used · Universal device compatible
                            </p>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    @if($downloadsEnabled)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4 bg-surface-container-lowest/80 p-4 rounded-DEFAULT">
                        <a href="{{ route('library') }}" class="flex items-center justify-center gap-2.5 h-12 px-5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm">
                            <span class="material-symbols-outlined text-[20px]">collections_bookmark</span>
                            <span>Go to My Library</span>
                        </a>
                        @if($firstDownloadUrl)
                        <a href="{{ $firstDownloadUrl }}" class="flex items-center justify-center gap-2.5 h-12 px-5 rounded-full bg-secondary text-on-secondary font-label-md text-label-md font-semibold hover:bg-on-secondary-container transition-all active:scale-[0.99] shadow-sm">
                            <span class="material-symbols-outlined text-[20px]">download</span>
                            <span>Download PDF directly</span>
                        </a>
                        @endif
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant text-center">
                        Download links expire 10 minutes after they are created. You can download each ebook up to {{ $downloadLimit }} times.
                    </p>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 text-on-surface-variant font-body-sm text-body-sm">
                        <a class="inline-flex items-center gap-1.5 text-secondary hover:text-on-surface transition-colors font-medium" href="{{ route('orders.show', $order) }}">
                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                            <span>View Printable Invoice</span>
                        </a>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-tertiary">mail</span>
                            <span>Lifetime download link dispatched</span>
                        </span>
                    </div>
                </div>

                <div class="mt-6 flex items-start gap-3 p-4 rounded-DEFAULT bg-surface-container/60 text-secondary">
                    <span class="material-symbols-outlined text-[20px] text-tertiary flex-shrink-0 mt-0.5">lock_open_right</span>
                    <p class="font-body-sm text-body-sm leading-relaxed">
                        <strong class="text-on-surface font-medium">Lifetime Access:</strong> Downloads are permanent and DRM-free. You can re-download this title anytime from your registered account library.
                    </p>
                </div>
            </section>
        @endif

        {{-- Help accordion --}}
        <div class="w-full bg-surface-container-lowest rounded-DEFAULT p-5 shadow-sm">
            <details class="group cursor-pointer">
                <summary class="flex items-center justify-between text-on-surface list-none">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-secondary text-[22px]">help_center</span>
                        <span class="font-label-md text-label-md font-semibold">Need help with your download or reading format?</span>
                    </div>
                    <span class="material-symbols-outlined text-secondary transition-transform duration-200 group-open:rotate-180">expand_more</span>
                </summary>
                <div class="mt-4 pt-4 border-t border-outline-variant/20 grid grid-cols-1 sm:grid-cols-2 gap-4 font-body-sm text-body-sm text-on-surface-variant">
                    <div class="p-3.5 rounded-DEFAULT bg-surface-container-low">
                        <p class="font-label-sm text-label-sm font-bold text-on-surface mb-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-tertiary">picture_as_pdf</span>
                            Reading on PC &amp; Tablet (PDF)
                        </p>
                        <p>Ideal for large displays, laptops, and tablets. Pre-formatted with high-resolution visual layouts.</p>
                    </div>
                    <div class="p-3.5 rounded-DEFAULT bg-surface-container-low">
                        <p class="font-label-sm text-label-sm font-bold text-on-surface mb-1 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-tertiary">phone_android</span>
                            Reading on Mobile (PDF)
                        </p>
                        <p>Open in Apple Books, Adobe Acrobat, Google Drive, or any native PDF reader for full interactive bookmarks.</p>
                    </div>
                </div>
            </details>
        </div>

        <div class="flex flex-col items-center text-center gap-2 pb-2">
            <a href="{{ route('books') }}" class="inline-flex items-center gap-1.5 font-body-sm text-body-sm font-bold text-secondary hover:underline">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Continue browsing more guides
            </a>
            <p class="font-body-sm text-body-sm text-on-surface-variant inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">info</span>
                <span>Need help? <a href="{{ route('contact') }}" class="text-secondary font-semibold underline underline-offset-2">Contact support</a></span>
            </p>
        </div>
    </div>
</div>

<script>
document.getElementById('copy-order')?.addEventListener('click', function () {
    const text = document.getElementById('order-ref')?.textContent.trim();
    const done = () => { this.innerHTML = '<span class="font-label-sm text-label-sm font-bold">Copied</span>'; };
    if (text && navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(done);
    } else { done(); }
});
</script>
@endsection
