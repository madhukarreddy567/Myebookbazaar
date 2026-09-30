{{-- Shared Stitch shell for error pages.
     Props: 'code' (404), 'chip' (status chip text), 'headline', 'copy'.
     Default slot: optional extra content under the copy (e.g. custom maintenance message). --}}
<div class="st-page w-full bg-surface py-10 px-4 sm:px-6">
    <div class="w-full max-w-[520px] mx-auto flex flex-col pb-8">
        {{-- Status chip --}}
        <div class="flex justify-center mb-4">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container-high text-secondary font-label-sm text-label-sm">
                <span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
                <span>Error {{ $code }} • {{ $chip }}</span>
            </div>
        </div>

        @include('errors.partials.illustration')

        {{-- Copy --}}
        <div class="text-center mt-2 mb-6 px-2">
            <h1 class="font-headline-md text-headline-md text-on-surface tracking-tight mb-2">
                {{ $headline }}
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-[340px] mx-auto leading-relaxed">
                {{ $copy }}
            </p>
            {{ $slot ?? '' }}
        </div>

        {{-- CTA stack --}}
        <div class="flex flex-col gap-2.5 w-full mb-6">
            <a class="w-full h-12 px-6 rounded-full bg-primary-container text-on-primary font-headline-sm text-headline-sm flex items-center justify-center gap-2 shadow-sm active:scale-[0.99] transition-transform hover:bg-primary" href="{{ route('home') }}">
                <span>Back to Home</span>
                <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
            </a>
            <a class="w-full h-12 px-6 rounded-full bg-surface-container text-secondary font-headline-sm text-headline-sm flex items-center justify-center gap-2 active:scale-[0.99] transition-transform hover:bg-secondary-container hover:text-on-surface" href="{{ route('books') }}">
                <span class="material-symbols-outlined text-[20px] text-primary-container">auto_stories</span>
                <span>Browse All Ebooks</span>
            </a>
            <div class="flex justify-center pt-1">
                <a class="inline-flex items-center gap-1 font-body-sm text-body-sm text-secondary hover:text-primary transition-colors py-1" href="{{ route('contact') }}">
                    <span class="material-symbols-outlined text-[16px]">support_agent</span>
                    <span>Contact Reader Support</span>
                </a>
            </div>
        </div>

        {{-- Quick recovery shelf --}}
        <div class="w-full bg-surface-container-lowest rounded-DEFAULT p-4 shadow-sm mb-6">
            <div class="flex items-center gap-2 mb-3">
                <span class="material-symbols-outlined text-primary-container text-[20px]">explore</span>
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Quick Recovery Shelf</h2>
            </div>
            <div class="flex flex-col gap-2">
                <a class="flex items-center justify-between p-3 rounded-DEFAULT bg-surface-container-low hover:bg-surface-container transition-colors" href="{{ route('library') }}">
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        <span class="material-symbols-outlined text-secondary text-[20px]">download_for_offline</span>
                        <div class="min-w-0">
                            <p class="font-label-md text-label-md text-on-surface truncate">Looking for your downloads?</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant truncate">Access My Digital Library</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-secondary text-[18px]">chevron_right</span>
                </a>
                <a class="flex items-center justify-between p-3 rounded-DEFAULT bg-surface-container-low hover:bg-surface-container transition-colors" href="{{ route('orders.index') }}">
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        <span class="material-symbols-outlined text-secondary text-[20px]">receipt_long</span>
                        <div class="min-w-0">
                            <p class="font-label-md text-label-md text-on-surface truncate">Check Payment Status</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant truncate">Orders, Invoices &amp; Receipts</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-secondary text-[18px]">chevron_right</span>
                </a>
            </div>
        </div>

        {{-- Trust badge --}}
        <div class="w-full bg-surface-container-high rounded-DEFAULT p-4 text-center">
            <div class="flex items-center justify-center gap-3 text-secondary font-label-sm text-label-sm mb-1 flex-wrap">
                <span class="inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-primary-container">lock_open</span>
                    100% DRM-Free
                </span>
                <span aria-hidden="true">•</span>
                <span class="inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-primary-container">bolt</span>
                    Instant Delivery
                </span>
                <span aria-hidden="true">•</span>
                <span class="inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-primary-container">lock</span>
                    Secure Razorpay
                </span>
            </div>
        </div>
    </div>
</div>
