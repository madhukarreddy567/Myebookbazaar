@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Open a support ticket — MyEbookBazaar')

@section('content')
<div class="st-page w-full max-w-[1040px] mx-auto px-4 sm:px-6 py-10 md:py-14">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10 pb-6 border-b border-surface-variant/40">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-fixed/40 text-on-primary-fixed-variant font-label-sm text-label-sm mb-3">
                <span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
                Help Desk
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Open a Ticket</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-xl">
                Tell us what's wrong — include your order number if it's about a purchase.
            </p>
        </div>
        <a href="{{ route('support.index') }}" class="inline-flex items-center gap-2 font-label-md text-label-md text-on-surface-variant hover:text-on-surface transition-colors py-2 px-1 self-start sm:self-auto">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to tickets</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <section class="lg:col-span-7 bg-surface-container-lowest p-6 sm:p-8 rounded-xl shadow-sm">
            <form method="POST" action="{{ route('support.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="subject">
                        Subject <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[20px] pointer-events-none">subject</span>
                        <input class="w-full h-12 pl-11 pr-4 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md placeholder:text-on-surface-variant/40 focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all @error('subject') ring-2 ring-error/50 @enderror" id="subject" name="subject" type="text" value="{{ old('subject') }}" maxlength="150" required placeholder="e.g. I can't download my ebook"/>
                    </div>
                    @error('subject')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="category">
                        What's it about? <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <select class="w-full h-12 pl-4 pr-10 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all appearance-none cursor-pointer @error('category') ring-2 ring-error/50 @enderror" id="category" name="category" required>
                            <option value="">Choose a topic…</option>
                            @foreach(\App\Models\Ticket::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/70 text-[20px] pointer-events-none">expand_more</span>
                    </div>
                    @error('category')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="order_id">
                        Related order <span class="font-normal text-on-surface-variant text-[13px]">(optional)</span>
                    </label>
                    <div class="relative">
                        <select class="w-full h-12 pl-4 pr-10 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all appearance-none cursor-pointer @error('order_id') ring-2 ring-error/50 @enderror" id="order_id" name="order_id">
                            <option value="">Not about an order</option>
                            @foreach($orders as $order)
                            <option value="{{ $order->id }}" @selected((string) old('order_id') === (string) $order->id)>
                                #{{ $order->order_number }} — {{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}
                            </option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/70 text-[20px] pointer-events-none">expand_more</span>
                    </div>
                    @error('order_id')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-label-md text-label-md text-on-surface mb-1.5 font-medium" for="message">
                        Message <span class="text-error">*</span>
                    </label>
                    <textarea class="w-full p-4 rounded-DEFAULT bg-surface-container-low/40 text-on-surface font-body-md text-body-md placeholder:text-on-surface-variant/40 focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container/40 transition-all resize-none @error('message') ring-2 ring-error/50 @enderror" id="message" name="message" rows="6" maxlength="5000" required placeholder="Describe the problem in detail…">{{ old('message') }}</textarea>
                    @error('message')<p class="font-body-sm text-body-sm text-error font-medium mt-1.5" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="pt-2 flex flex-wrap items-center gap-4">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 min-w-[200px] h-12 px-8 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold tracking-wide hover:bg-primary shadow-sm active:scale-[0.99] transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        <span>Send ticket</span>
                    </button>
                    <a href="{{ route('support.index') }}" class="font-label-md text-label-md font-medium text-on-surface-variant hover:text-on-surface">Cancel</a>
                </div>
            </form>
        </section>

        <div class="lg:col-span-5 space-y-6">
            <div class="bg-surface-container-low/70 p-6 sm:p-7 rounded-xl">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-primary text-[20px]">tips_and_updates</span>
                    <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold text-[18px]">Before you write</h3>
                </div>
                <div class="space-y-3 font-body-sm text-body-sm text-on-surface-variant">
                    <p class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0 mt-0.5">check_circle</span>
                        <span>Download links in your library refresh on every visit and expire after 10 minutes.</span>
                    </p>
                    <p class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0 mt-0.5">check_circle</span>
                        <span>Each ebook includes a limited number of downloads — the remaining count is shown in your library.</span>
                    </p>
                    <p class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[18px] text-secondary shrink-0 mt-0.5">check_circle</span>
                        <span>Include your order number for anything payment-related so we can find it instantly.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
