@extends('layouts.app')

@section('robots', 'noindex,nofollow')

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
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My account']]" />

    {{-- Profile header --}}
    <section class="mt-4 st-card p-5 md:p-6">
        <div class="flex items-start gap-3.5">
            <span class="relative shrink-0">
                <span class="st-avatar" aria-hidden="true">{{ $initials }}</span>
                <span class="absolute bottom-0 right-0 w-4 h-4 bg-[#F5A623] rounded-full ring-2 ring-white flex items-center justify-center" aria-hidden="true">
                    <x-ui.icon name="check" class="w-2.5 h-2.5 text-white" />
                </span>
            </span>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h1 class="st-h text-[22px] truncate">Hello, {{ $firstName }}!</h1>
                    @if($guidesCount > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#FFF0D6] text-[#B45309] text-[10px] font-bold uppercase tracking-wider">
                        <x-ui.icon name="check-circle" class="w-3 h-3" /> Pro member
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#EAF3F5] text-[#0B3D4A] text-[10px] font-bold uppercase tracking-wider">
                        <x-ui.icon name="user" class="w-3 h-3" /> Member
                    </span>
                    @endif
                </div>
                <p class="text-sm st-muted truncate mt-0.5">{{ $maskedEmail }}</p>
                <p class="text-xs font-bold text-[#0B3D4A] mt-1 inline-flex items-center gap-1">
                    <x-ui.icon name="book" class="w-4 h-4" /> Digital reader since {{ $readerSince }}
                </p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-2 mt-4 rounded-xl bg-[#F1F4FA] p-3 text-center">
            <div>
                <p class="st-stat-num">{{ $guidesCount }}</p>
                <p class="text-[10px] st-muted mt-1 leading-tight font-medium">Purchased {{ $guidesCount === 1 ? 'guide' : 'guides' }}</p>
            </div>
            <div>
                <p class="st-stat-num !text-[#D97706]">{{ $filesCount }}</p>
                <p class="text-[10px] st-muted mt-1 leading-tight font-medium">Available files</p>
            </div>
            <div>
                <p class="st-stat-num" aria-hidden="true">∞</p>
                <p class="text-[10px] st-muted mt-1 leading-tight font-medium">Lifetime access</p>
            </div>
        </div>
    </section>

    {{-- Ready-to-read spotlight --}}
    @if($latestItem && $latestItem->book)
    <section class="st-darkpanel p-5 mt-4">
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <p class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-[#BDE3E4]">
                    <span class="w-2 h-2 rounded-full bg-[#F5A623] animate-pulse" aria-hidden="true"></span> Ready to read
                </p>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/15 text-white">Order #{{ $latestOrder->order_number }}</span>
            </div>
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/stitch/book-mockup-3d-alt.jpg') }}" alt="" class="w-16 h-[5.5rem] object-cover rounded-md shadow-lg shrink-0" loading="lazy">
                <div class="flex-1 min-w-0">
                    <h2 class="st-h !text-white text-[20px] uppercase leading-tight line-clamp-2">{{ $latestItem->book->title }}</h2>
                    <p class="text-xs text-white/70 mt-0.5 line-clamp-1">
                        @if($latestItem->book->pages){{ $latestItem->book->pages }}-page PDF · @endif
                        {{ $latestItem->book->language ?? 'English' }}
                    </p>
                    <a href="{{ route('library') }}" class="st-btn st-btn-amber !min-h-[40px] !px-5 !text-[13px] mt-2.5">
                        <x-ui.icon name="book" class="w-4 h-4" /> Open library
                    </a>
                </div>
            </div>
        </div>
    </section>
    @else
    <section class="st-darkpanel p-5 mt-4">
        <div class="relative z-10">
            <h2 class="st-h !text-white text-[20px] uppercase">Your library is empty</h2>
            <p class="text-sm text-white/70 mt-1">Grab your first guide and start reading in minutes.</p>
            <a href="{{ route('books') }}" class="st-btn st-btn-amber !min-h-[40px] !px-5 !text-[13px] mt-3">
                Browse ebooks <x-ui.icon name="arrow-right" class="w-4 h-4" />
            </a>
        </div>
    </section>
    @endif

    {{-- Menu --}}
    <section class="mt-6" x-data="{ signoutOpen: false }">
        <div class="px-1 flex items-center justify-between mb-2.5">
            <h2 class="text-xs font-bold uppercase tracking-wider st-muted">Account management</h2>
            <span class="text-[11px] text-[#0B3D4A] font-medium">Direct portal</span>
        </div>
        <div class="space-y-2.5">
            <a href="{{ route('library') }}" class="st-menu-row group">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="st-menu-ic"><x-ui.icon name="book" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block">My digital library</span>
                        <span class="text-sm st-muted block truncate">View &amp; re-download your purchased ebooks</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 st-muted shrink-0 ml-2" />
            </a>
            <a href="{{ route('orders.index') }}" class="st-menu-row group">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="st-menu-ic"><x-ui.icon name="file-text" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block">Orders &amp; tax invoices</span>
                        <span class="text-sm st-muted block truncate">Order history, invoices &amp; IDs</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 st-muted shrink-0 ml-2" />
            </a>
            <a href="{{ route('support.index') }}" class="st-menu-row group">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="st-menu-ic"><x-ui.icon name="mail" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block">Support tickets</span>
                        <span class="text-sm st-muted block truncate">Ask us about orders, downloads &amp; refunds</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 st-muted shrink-0 ml-2" />
            </a>
            <a href="#profile" class="st-menu-row group">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="st-menu-ic"><x-ui.icon name="user" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block">Profile &amp; email delivery</span>
                        <span class="text-sm st-muted block truncate">Delivery email, billing info &amp; password</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 st-muted shrink-0 ml-2" />
            </a>
            <a href="{{ route('faq') }}" class="st-menu-row group">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="st-menu-ic"><x-ui.icon name="info" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block">Digital reading assistance</span>
                        <span class="text-sm st-muted block truncate">PDF apps, Kindle transfer &amp; sync guides</span>
                    </span>
                </span>
                <x-ui.icon name="arrow-right" class="w-5 h-5 st-muted shrink-0 ml-2" />
            </a>
            <button type="button" @click="signoutOpen = true"
                    class="st-menu-row w-full text-left !border-[#F3D9D9] !bg-[#FDF6F6]">
                <span class="flex items-center gap-3 min-w-0">
                    <span class="st-menu-ic !bg-[#FDECEC] !text-[#B3372F]"><x-ui.icon name="logout" /></span>
                    <span class="min-w-0">
                        <span class="font-bold block text-[#B3372F]">Sign out from this device</span>
                        <span class="text-sm st-muted block truncate">Secure session ending for {{ $maskedEmail }}</span>
                    </span>
                </span>
                <x-ui.icon name="logout" class="w-5 h-5 text-[#B3372F] shrink-0 ml-2" />
            </button>
        </div>

        {{-- Sign-out confirmation --}}
        <div x-show="signoutOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-label="Sign out confirmation">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl" @click.away="signoutOpen = false">
                <p class="font-bold text-lg">Sign out?</p>
                <p class="text-sm st-muted mt-1">Your downloaded PDFs stay on this device. You can sign back in anytime.</p>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="signoutOpen = false" class="st-btn st-btn-teal !min-h-[44px] !px-5 !text-[14px]">Cancel</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="st-btn !min-h-[44px] !px-5 !text-[14px] bg-[#B3372F] !text-white hover:bg-[#8F2B24]">Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="mt-4 rounded-2xl bg-[#F1F4FA] p-4">
        <p class="font-bold inline-flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-[#0B3D4A] text-white inline-flex items-center justify-center shrink-0"><x-ui.icon name="lock" class="w-3.5 h-3.5" /></span>
            Direct digital access guarantee
        </p>
        <p class="text-sm st-muted mt-1.5 leading-relaxed">All purchased materials remain tied to your primary registered email. Secure digital delivery.</p>
        <p class="mt-2 flex items-center justify-between text-xs">
            <span class="inline-flex items-center gap-1.5 st-muted"><span class="w-1.5 h-1.5 rounded-full bg-[#1E8E5A]" aria-hidden="true"></span> Account active</span>
            <a href="{{ route('contact') }}" class="font-bold text-[#0B3D4A] inline-flex items-center gap-1"><x-ui.icon name="mail" class="w-3.5 h-3.5" /> {{ settings('site.support_email', 'support@myebookbazaar.com') }}</a>
        </p>
    </section>

    {{-- Profile & security (existing functionality, restyled) --}}
    <div id="profile" class="grid md:grid-cols-2 gap-5 mt-6 items-start scroll-mt-24">
        <div class="st-card p-6">
            <h2 class="st-h text-[22px]">Profile</h2>
            @if(session('status'))
            <x-ui.alert type="success" class="mt-3">{{ session('status') }}</x-ui.alert>
            @endif
            <form method="POST" action="{{ route('account.profile') }}" class="mt-4 flex flex-col gap-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="st-label" for="pname">Name</label>
                    <input id="pname" name="name" type="text" value="{{ old('name', $user->name) }}"
                           required autocomplete="name" class="st-input">
                    @if($errors->first('name'))
                    <p class="st-field-error" role="alert">{{ $errors->first('name') }}</p>
                    @endif
                </div>
                <div>
                    <label class="st-label" for="pemail">Email</label>
                    <input id="pemail" name="email" type="email" value="{{ old('email', $user->email) }}"
                           required autocomplete="email" class="st-input">
                    <p class="st-hint mt-1.5">Order receipts and ebook delivery go to this email.</p>
                    @if($errors->first('email'))
                    <p class="st-field-error" role="alert">{{ $errors->first('email') }}</p>
                    @endif
                </div>
                <button type="submit" class="st-btn st-btn-teal self-start">Save profile</button>
            </form>
            <dl class="mt-4 divide-y divide-[#EDF1F7]">
                <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                    <dt class="st-muted text-sm">Country</dt>
                    <dd class="font-bold">{{ $user->country_name }} ({{ $user->country_code }})</dd>
                </div>
                <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                    <dt class="st-muted text-sm">Pricing region</dt>
                    <dd><span class="text-[11px] font-extrabold uppercase tracking-wider bg-[#EDF1F7] text-[#0B3D4A] rounded-md px-2 py-1">{{ $user->pricing_region === 'IN' ? 'India — prices in ₹' : 'International — prices in $' }}</span></dd>
                </div>
            </dl>
            <p class="st-muted text-xs mt-4 flex gap-2">
                <x-ui.icon name="lock" class="w-4 h-4 shrink-0" />
                <span>Country cannot be changed after signup. Contact support if you need it updated.</span>
            </p>
        </div>

        <div class="st-card p-6">
            <h2 class="st-h text-[22px]">Change password</h2>
            <form method="POST" action="{{ route('account.password') }}" class="mt-4 flex flex-col gap-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="st-label" for="cpass">Current password</label>
                    <input id="cpass" name="current_password" type="password"
                           required autocomplete="current-password" class="st-input">
                    @if($errors->first('current_password'))
                    <p class="st-field-error" role="alert">{{ $errors->first('current_password') }}</p>
                    @endif
                </div>
                <div>
                    <label class="st-label" for="npass">New password</label>
                    <input id="npass" name="password" type="password"
                           required autocomplete="new-password" class="st-input">
                    <p class="st-hint mt-1.5">Min 8 characters, with upper + lower case, a number and a symbol.</p>
                    @if($errors->first('password'))
                    <p class="st-field-error" role="alert">{{ $errors->first('password') }}</p>
                    @endif
                </div>
                <div>
                    <label class="st-label" for="npass2">Confirm new password</label>
                    <input id="npass2" name="password_confirmation" type="password"
                           required autocomplete="new-password" class="st-input">
                </div>
                <button type="submit" class="st-btn st-btn-teal self-start">Update password</button>
            </form>
        </div>
    </div>

    {{-- Phase 6: privacy controls --}}
    <div class="grid md:grid-cols-2 gap-5 mt-5 items-start">
        <div class="st-card p-6">
            <h2 class="st-h text-[22px]">My data</h2>
            <p class="st-muted text-sm mt-2">Download everything we hold about you — profile, orders, payments and download history — as a JSON file.</p>
            <a href="{{ route('account.export') }}" class="st-btn st-btn-teal !min-h-[48px] mt-4">Download my data</a>
        </div>
        <div class="st-card p-6">
            <h2 class="st-h text-[22px]">Delete my account</h2>
            <p class="st-muted text-sm mt-2">Permanently scrub your personal details. Order records are kept for tax purposes, without your name or email.</p>
            <a href="{{ route('account.delete') }}" class="st-btn !min-h-[48px] mt-4 bg-[#B3372F] !text-white hover:bg-[#8F2B24]">Delete my account…</a>
        </div>
    </div>
</div>
@endsection
