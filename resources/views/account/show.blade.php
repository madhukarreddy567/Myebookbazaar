@extends('layouts.app')

@section('title', 'My account — MyEbookBazaar')

@php
$paidOrdersQuery = $user->orders()->where('status', \App\Models\Order::STATUS_PAID);
$guidesCount = (clone $paidOrdersQuery)->count();
$latestOrder = (clone $paidOrdersQuery)->latest()->first();
$latestItem = $latestOrder ? $latestOrder->items()->with('book')->first() : null;
$filesCount = \App\Models\OrderItem::whereHas('order', fn ($q) => $q
    ->where('user_id', $user->id)
    ->where('status', \App\Models\Order::STATUS_PAID)
)->distinct()->count('book_id');
$firstName = explode(' ', (string) $user->name)[0] ?? $user->name;
$initials = collect(explode(' ', (string) $user->name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
$maskedEmail = preg_replace('/^(.).*(@.*)$/', '$1***$2', (string) $user->email);
$readerSince = $user->created_at ? $user->created_at->format('Y') : date('Y');
@endphp

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-8">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My account']]" />

    {{-- Profile header --}}
    <section class="mt-4 relative overflow-hidden rounded-2xl bg-white dark:bg-surface p-5 shadow-sm border border-line">
        <div class="absolute -right-12 -top-12 w-36 h-36 rounded-full bg-tint-teal-strong blur-2xl pointer-events-none" aria-hidden="true"></div>
        <div class="relative z-10 flex items-start gap-3.5">
            <span class="relative shrink-0">
                <span class="w-14 h-14 rounded-full bg-teal-900 text-white flex items-center justify-center font-display text-xl font-extrabold shadow-md" aria-hidden="true">{{ $initials }}</span>
                <span class="absolute bottom-0 right-0 w-4 h-4 bg-saffron-500 rounded-full ring-2 ring-white flex items-center justify-center" aria-hidden="true">
                    <x-ui.icon name="check" class="w-2.5 h-2.5 text-white" />
                </span>
            </span>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h1 class="text-xl font-extrabold truncate">Hello, {{ $firstName }}!</h1>
                    @if($guidesCount > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-saffron-100 text-saffron-600 text-[10px] font-bold uppercase tracking-wider">
                        <x-ui.icon name="check-circle" class="w-3 h-3" /> Pro member
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-teal-100 text-teal-900 text-[10px] font-bold uppercase tracking-wider">
                        <x-ui.icon name="user" class="w-3 h-3" /> Member
                    </span>
                    @endif
                </div>
                <p class="text-sm text-mutedtext truncate mt-0.5">{{ $maskedEmail }}</p>
                <p class="text-xs font-bold text-teal-900 mt-1 inline-flex items-center gap-1">
                    <x-ui.icon name="book" class="w-4 h-4" /> Digital reader since {{ $readerSince }}
                </p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-2 mt-4 rounded-xl bg-tint-teal p-3 text-center">
            <div>
                <p class="font-display text-2xl font-extrabold text-teal-900 leading-none">{{ $guidesCount }}</p>
                <p class="text-[10px] text-mutedtext mt-1 leading-tight font-medium">Purchased {{ $guidesCount === 1 ? 'guide' : 'guides' }}</p>
            </div>
            <div>
                <p class="font-display text-2xl font-extrabold text-saffron-600 leading-none">{{ $filesCount }}</p>
                <p class="text-[10px] text-mutedtext mt-1 leading-tight font-medium">Available files</p>
            </div>
            <div>
                <p class="font-display text-2xl font-extrabold text-teal-900 leading-none" aria-hidden="true">∞</p>
                <p class="text-[10px] text-mutedtext mt-1 leading-tight font-medium">Lifetime access</p>
            </div>
        </div>
    </section>

    {{-- Ready-to-read spotlight --}}
    @if($latestItem && $latestItem->book)
    <section class="mt-4 rounded-2xl bg-gradient-to-br from-teal-900 to-teal-700 text-white p-5 shadow-lg relative overflow-hidden">
        <div class="absolute -right-8 -bottom-10 w-32 h-32 rounded-full bg-glow-saffron blur-xl pointer-events-none" aria-hidden="true"></div>
        <div class="flex items-center justify-between mb-3">
            <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-teal-100">
                <span class="w-2 h-2 rounded-full bg-saffron-500 animate-pulse" aria-hidden="true"></span> Ready to read &amp; sync
            </p>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/15 text-teal-100">Order #{{ $latestOrder->order_number }}</span>
        </div>
        <div class="flex items-center gap-3.5">
            <img src="{{ asset('images/stitch/book-mockup-3d-alt.jpg') }}" alt="" class="w-16 h-[5.5rem] object-cover rounded-md shadow-lg shrink-0" loading="lazy">
            <div class="flex-1 min-w-0">
                <h2 class="display-sm uppercase leading-tight text-white line-clamp-2">{{ $latestItem->book->title }}</h2>
                <p class="text-xs text-teal-100 mt-0.5 line-clamp-1">
                    @if($latestItem->book->pages){{ $latestItem->book->pages }}-page PDF · @endif
                    {{ $latestItem->book->language ?? 'English' }}
                </p>
                <x-ui.button href="{{ route('library') }}" size="sm" class="mt-2.5">
                    <span class="inline-flex items-center gap-1.5 uppercase tracking-wider"><x-ui.icon name="book" class="w-4 h-4" /> Open library</span>
                </x-ui.button>
            </div>
        </div>
    </section>
    @else
    <section class="mt-4 rounded-2xl bg-gradient-to-br from-teal-900 to-teal-700 text-white p-5 shadow-lg">
        <h2 class="display-sm uppercase text-white">Your library is empty</h2>
        <p class="text-sm text-teal-100 mt-1">Grab your first guide and start reading in minutes.</p>
        <x-ui.button href="{{ route('books') }}" size="sm" class="mt-3">
            <span class="inline-flex items-center gap-1.5 uppercase tracking-wider">Browse ebooks <x-ui.icon name="arrow-right" class="w-4 h-4" /></span>
        </x-ui.button>
    </section>
    @endif

    {{-- Menu --}}
    <section class="mt-6" x-data="{ signoutOpen: false }">
        <div class="px-1 flex items-center justify-between mb-2.5">
            <h2 class="text-xs font-bold uppercase tracking-wider text-mutedtext">Account management</h2>
            <span class="text-[11px] text-teal-900 font-medium">Direct portal</span>
        </div>
        <div class="space-y-2.5">
            <a href="{{ route('library') }}" class="group flex items-center justify-between p-3.5 rounded-2xl bg-white dark:bg-surface border border-line shadow-sm active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-xl bg-teal-100 text-teal-900 flex items-center justify-center shrink-0"><x-ui.icon name="book" class="w-5 h-5" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block group-hover:text-teal-900">My digital library</span>
                        <span class="text-sm text-mutedtext block truncate">View &amp; re-download your purchased ebooks</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 text-mutedtext shrink-0 ml-2" />
            </a>
            <a href="{{ route('orders.index') }}" class="group flex items-center justify-between p-3.5 rounded-2xl bg-white dark:bg-surface border border-line shadow-sm active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-xl bg-saffron-100 text-saffron-600 flex items-center justify-center shrink-0"><x-ui.icon name="file-text" class="w-5 h-5" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block group-hover:text-teal-900">Orders &amp; tax invoices</span>
                        <span class="text-sm text-mutedtext block truncate">Order history, GST receipts &amp; IDs</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 text-mutedtext shrink-0 ml-2" />
            </a>
            <a href="#profile" class="group flex items-center justify-between p-3.5 rounded-2xl bg-white dark:bg-surface border border-line shadow-sm active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-xl bg-teal-100 text-teal-900 flex items-center justify-center shrink-0"><x-ui.icon name="user" class="w-5 h-5" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block group-hover:text-teal-900">Profile &amp; email delivery</span>
                        <span class="text-sm text-mutedtext block truncate">Delivery email, billing info &amp; password</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 text-mutedtext shrink-0 ml-2" />
            </a>
            <a href="{{ route('faq') }}" class="group flex items-center justify-between p-3.5 rounded-2xl bg-white dark:bg-surface border border-line shadow-sm active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-xl bg-teal-100 text-teal-900 flex items-center justify-center shrink-0"><x-ui.icon name="info" class="w-5 h-5" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block group-hover:text-teal-900">Digital reading assistance</span>
                        <span class="text-sm text-mutedtext block truncate">PDF apps, Kindle transfer &amp; sync guides</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 text-mutedtext shrink-0 ml-2" />
            </a>
            <button type="button" @click="signoutOpen = true"
                    class="w-full group flex items-center justify-between p-3.5 rounded-2xl bg-tint-danger border border-tint-danger text-left active:scale-[0.99] transition-all">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="w-10 h-10 rounded-xl bg-tint-danger-strong text-danger flex items-center justify-center shrink-0"><x-ui.icon name="logout" class="w-5 h-5" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block text-danger">Sign out from this device</span>
                        <span class="text-sm text-mutedtext block truncate">Secure session ending for {{ $maskedEmail }}</span>
                    </span>
                </span>
                <x-ui.icon name="logout" class="w-5 h-5 text-danger shrink-0 ml-2" />
            </button>
        </div>

        {{-- Sign-out confirmation --}}
        <div x-show="signoutOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-label="Sign out confirmation">
            <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-surface p-5 shadow-2xl" @click.away="signoutOpen = false">
                <p class="font-bold text-lg">Sign out?</p>
                <p class="text-sm text-mutedtext mt-1">Your downloaded PDFs stay on this device. You can sign back in anytime.</p>
                <div class="flex justify-end gap-2 mt-4">
                    <x-ui.button variant="secondary" size="sm" @click="signoutOpen = false">Cancel</x-ui.button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button variant="danger" size="sm" type="submit">Sign out</x-ui.button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="mt-4 rounded-2xl bg-tint-teal p-4">
        <p class="font-bold inline-flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-teal-900 text-white inline-flex items-center justify-center shrink-0"><x-ui.icon name="lock" class="w-3.5 h-3.5" /></span>
            Direct digital access guarantee
        </p>
        <p class="text-sm text-mutedtext mt-1.5 leading-relaxed">All purchased materials remain tied to your primary registered email. 256-bit SSL encrypted digital delivery pipeline.</p>
        <p class="mt-2 flex items-center justify-between text-xs">
            <span class="inline-flex items-center gap-1.5 text-mutedtext"><span class="w-1.5 h-1.5 rounded-full bg-success" aria-hidden="true"></span> Vault sync active</span>
            <a href="{{ route('contact') }}" class="font-bold text-teal-900 inline-flex items-center gap-1"><x-ui.icon name="mail" class="w-3.5 h-3.5" /> {{ settings('site.support_email', 'support@myebookbazaar.com') }}</a>
        </p>
    </section>

    {{-- Profile & security (existing functionality, restyled) --}}
    <div id="profile" class="grid md:grid-cols-2 gap-5 mt-6 items-start scroll-mt-24">
        <x-ui.card class="p-6">
            <h2 class="text-2xl font-bold">Profile</h2>
            @if(session('status'))
            <x-ui.alert type="success" class="mt-3">{{ session('status') }}</x-ui.alert>
            @endif
            <form method="POST" action="{{ route('account.profile') }}" class="mt-4">
                @csrf
                @method('PUT')

                <x-ui.input label="Name" name="name" :value="old('name', $user->name)"
                            required autocomplete="name"
                            :error="$errors->first('name')" />

                <x-ui.input label="Email" name="email" type="email" :value="old('email', $user->email)"
                            required autocomplete="email"
                            hint="Order receipts and ebook delivery go to this email."
                            :error="$errors->first('email')" />

                <x-ui.button type="submit">Save profile</x-ui.button>
            </form>
            <dl class="mt-4 divide-y divide-line">
                <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                    <dt class="text-mutedtext text-sm">Country</dt>
                    <dd class="font-bold">{{ $user->country_name }} ({{ $user->country_code }})</dd>
                </div>
                <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                    <dt class="text-mutedtext text-sm">Pricing region</dt>
                    <dd><x-ui.badge type="info">{{ $user->pricing_region === 'IN' ? 'India — prices in ₹' : 'International — prices in $' }}</x-ui.badge></dd>
                </div>
            </dl>
            <p class="text-mutedtext text-xs mt-4 flex gap-2">
                <x-ui.icon name="lock" class="w-4 h-4 shrink-0" />
                <span>Country cannot be changed after signup. Contact support if you need it updated.</span>
            </p>
        </x-ui.card>

        <x-ui.card class="p-6">
            <h2 class="text-2xl font-bold">Change password</h2>
            <form method="POST" action="{{ route('account.password') }}" class="mt-4">
                @csrf
                @method('PUT')

                <x-ui.input label="Current password" name="current_password" type="password"
                            required autocomplete="current-password"
                            :error="$errors->first('current_password')" />

                <x-ui.input label="New password" name="password" type="password"
                            required autocomplete="new-password"
                            hint="Min 8 characters, with upper + lower case and a number."
                            :error="$errors->first('password')" />

                <x-ui.input label="Confirm new password" name="password_confirmation" type="password"
                            required autocomplete="new-password" />

                <x-ui.button type="submit">Update password</x-ui.button>
            </form>
        </x-ui.card>
    </div>

    {{-- Phase 6: privacy controls --}}
    <div class="grid md:grid-cols-2 gap-5 mt-5 items-start">
        <x-ui.card class="p-6">
            <h2 class="text-2xl font-bold">My data</h2>
            <p class="text-mutedtext text-sm mt-2">Download everything we hold about you — profile, orders, payments and download history — as a JSON file.</p>
            <x-ui.button href="{{ route('account.export') }}" variant="secondary" class="mt-4">Download my data</x-ui.button>
        </x-ui.card>

        <x-ui.card class="p-6">
            <h2 class="text-2xl font-bold">Delete my account</h2>
            <p class="text-mutedtext text-sm mt-2">Permanently scrub your personal details. Order records are kept for tax purposes, without your name or email.</p>
            <x-ui.button href="{{ route('account.delete') }}" variant="secondary" class="mt-4">Delete my account…</x-ui.button>
        </x-ui.card>
    </div>
</div>
@endsection
