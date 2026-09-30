@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Thank you — MyEbookBazaar')

@php
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
@endphp

@section('content')
<div class="max-w-container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        {{-- Success hero --}}
        <section class="flex flex-col items-center text-center pt-2 pb-6 relative">
            <div class="relative flex items-center justify-center mb-4">
                <div class="absolute w-20 h-20 rounded-full bg-glow-saffron blur-xl" aria-hidden="true"></div>
                <span class="relative w-16 h-16 rounded-full bg-teal-900 text-white flex items-center justify-center shadow-lg" aria-hidden="true">
                    <x-ui.icon name="check" class="w-9 h-9" />
                </span>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-100 text-teal-900 text-[11px] font-bold uppercase tracking-wider mb-2">
                <x-ui.icon name="check-circle" class="w-4 h-4" /> Transaction confirmed
            </span>
            <h1 class="display-lg text-teal-900 uppercase">Payment successful!</h1>
            <p class="mt-2 text-mutedtext max-w-sm">Thank you for your purchase. Your digital guide is ready for immediate access.</p>
        </section>

        {{-- Dispatch notification --}}
        <section class="mb-4">
            <div class="rounded-2xl bg-tint-teal p-4 flex items-start gap-3">
                <span class="w-9 h-9 rounded-full bg-teal-900 text-white flex items-center justify-center shrink-0" aria-hidden="true">
                    <x-ui.icon name="mail" class="w-5 h-5" />
                </span>
                <div class="min-w-0">
                    <p class="font-bold text-teal-900">Permanent link dispatched</p>
                    <p class="text-sm text-mutedtext leading-snug mt-0.5">
                        An email with your lifetime download link and GST invoice has been dispatched to
                        <strong class="text-inktext">{{ $maskedEmail }}</strong>.
                    </p>
                </div>
            </div>
        </section>

        {{-- Order reference --}}
        <x-ui.card class="p-5 mb-4">
            <div class="flex items-start justify-between gap-3 pb-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-mutedtext">Order reference</p>
                    <p class="mt-0.5 flex items-center gap-1.5">
                        <span class="font-extrabold text-teal-900 tracking-wide" id="order-ref">{{ $order->order_number }}</span>
                        <button type="button" id="copy-order" aria-label="Copy order reference"
                                class="w-7 h-7 inline-flex items-center justify-center rounded-md bg-tint-teal-strong text-teal-900 hover:bg-teal-100 transition-colors">
                            <x-ui.icon name="file-text" class="w-4 h-4" />
                        </button>
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-teal-100 text-teal-900 text-[11px] font-bold uppercase tracking-wider shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-700" aria-hidden="true"></span> Paid &amp; fulfilled
                </span>
            </div>
            <div class="grid grid-cols-2 gap-2 rounded-xl bg-tint-teal p-3">
                <div>
                    <p class="text-[11px] text-mutedtext">Timestamp</p>
                    <p class="text-sm font-bold mt-0.5">{{ $paidAt }}</p>
                </div>
                <div>
                    <p class="text-[11px] text-mutedtext">Amount paid</p>
                    <p class="price text-sm font-bold mt-0.5 truncate">{{ $symbol }}{{ number_format((float) $order->grand_total, 2) }} {{ $order->currency }}</p>
                </div>
            </div>
            @if((float) $order->gst_rate > 0)
            <div class="mt-2 rounded-xl bg-tint-teal p-3 text-sm flex justify-between items-center">
                <span class="text-mutedtext">Includes GST ({{ $gstRateLabel }})</span>
                <span class="price font-bold">{{ $symbol }}{{ number_format((float) $order->gst_amount, 2) }}</span>
            </div>
            @endif
        </x-ui.card>

        @if($errors->any())
            <x-ui.alert type="warning" class="mb-4">{{ $errors->first() }}</x-ui.alert>
        @endif

        {{-- Purchased items --}}
        @if($downloadsEnabled)
        <section class="mb-5">
            <x-ui.card class="p-5">
                <h2 class="display-sm text-teal-900 uppercase">Your ebooks</h2>
                <p class="text-sm text-mutedtext mt-1">Download links expire 10 minutes after they are created.
                You can download each ebook up to {{ $downloadLimit }} times.</p>
                <div class="mt-4 space-y-3">
                    @foreach($order->items as $item)
                    <div class="flex items-center gap-3.5 rounded-xl border border-line p-3.5">
                        <span class="w-16 shrink-0">
                            @if($item->book && $item->book->coverUrl())
                                <img src="{{ $item->book->coverUrl() }}" alt="" class="rounded-lg w-full h-auto shadow" loading="lazy">
                            @elseif($item->book && str_contains($item->book->slug, 'fit-at-home'))
                                <img src="{{ asset('images/stitch/book-mockup-3d.jpg') }}" alt="" class="rounded-lg w-full h-auto shadow" loading="lazy">
                            @else
                                <span class="book-cover-placeholder aspect-[3/4] rounded-lg !p-2" role="img" aria-label="Cover coming soon"><span class="!text-xs">{{ $item->title }}</span></span>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-full bg-teal-100 text-teal-900 text-[10px] font-bold tracking-wide uppercase">PDF ebook</span>
                                @if($item->book && $item->book->pages)
                                <span class="text-xs text-mutedtext">• {{ $item->book->pages }} pages</span>
                                @endif
                            </span>
                            <span class="font-bold block line-clamp-2 mt-1">{{ $item->title }}</span>
                            <span class="text-xs text-mutedtext block mt-1">
                                <x-ui.icon name="download" class="w-3.5 h-3.5 inline -mt-0.5" />
                                {{ \App\Models\DownloadLog::usedCount($order->id, $item->book_id) }} of {{ $downloadLimit }} downloads used · Universal device compatible
                            </span>
                        </span>
                    </div>
                    @endforeach
                </div>
            </x-ui.card>
        </section>
        @endif

        {{-- CTAs --}}
        <section class="flex flex-col gap-2.5 mb-5">
            <x-ui.button href="{{ route('library') }}" size="lg" class="w-full uppercase tracking-wider shadow-lg shadow-glow-saffron">
                <span class="inline-flex items-center gap-2"><x-ui.icon name="book" class="w-5 h-5" /> Access your ebook now</span>
            </x-ui.button>
            @if($firstDownloadUrl)
            <x-ui.button variant="secondary" href="{{ $firstDownloadUrl }}" size="lg" class="w-full uppercase tracking-wider">
                <span class="inline-flex items-center gap-2"><x-ui.icon name="download" class="w-5 h-5" /> Download PDF directly</span>
            </x-ui.button>
            @endif
        </section>

        {{-- Quick start --}}
        <x-ui.card class="p-5 mb-6">
            <h3 class="font-bold text-teal-900 inline-flex items-center gap-1.5">
                <x-ui.icon name="zap" class="w-5 h-5" /> Quick start instructions
            </h3>
            <ol class="mt-3 space-y-2.5 text-sm text-mutedtext">
                <li class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-900 text-[11px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>
                    <span>Click <strong class="text-inktext">Download PDF</strong> to save the DRM-free guide directly onto your smartphone, tablet, or laptop.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-900 text-[11px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>
                    <span>Open in Apple Books, Adobe Acrobat, Google Drive, or any native PDF reader for full interactive bookmarks.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-teal-100 text-teal-900 text-[11px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>
                    <span>Access updates and re-download anytime via your MyEbookBazaar secure user library.</span>
                </li>
            </ol>
        </x-ui.card>

        <footer class="flex flex-col items-center text-center gap-2 pb-2">
            <a href="{{ route('books') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-teal-900 hover:underline">
                <x-ui.icon name="arrow-right" class="w-4 h-4 rotate-180" /> Continue browsing more guides
            </a>
            <p class="text-xs text-mutedtext inline-flex items-center gap-1.5">
                <x-ui.icon name="info" class="w-4 h-4" />
                <span>Need help? Contact <a href="{{ route('contact') }}" class="text-teal-900 font-semibold underline underline-offset-2">{{ settings('site.support_email', 'support@myebookbazaar.com') }}</a></span>
            </p>
        </footer>
    </div>
</div>

<script>
document.getElementById('copy-order')?.addEventListener('click', function () {
    const text = document.getElementById('order-ref').textContent.trim();
    const done = () => { this.innerHTML = '<span class="text-xs font-bold">Copied</span>'; };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(done);
    } else { done(); }
});
</script>
@endsection
