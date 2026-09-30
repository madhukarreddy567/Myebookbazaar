@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'My account — MyEbookBazaar')

@php
$paidOrdersQuery = $user->orders()->where('status', \App\Models\Order::STATUS_PAID);
$guidesCount = (clone $paidOrdersQuery)->count();
$recentOrders = $user->orders()->with('items')->latest()->take(5)->get();
$libraryItems = \App\Models\OrderItem::whereHas('order', fn ($q) => $q
    ->where('user_id', $user->id)
    ->where('status', \App\Models\Order::STATUS_PAID))
    ->with(['book', 'order'])
    ->latest()
    ->get()
    ->unique('book_id')
    ->values();
$filesCount = $libraryItems->count();
$firstName = explode(' ', (string) $user->name)[0] ?? $user->name;
$maskedEmail = preg_replace('/^(.).*(@.*)$/', '$1***$2', (string) $user->email);
$readerSince = $user->created_at ? $user->created_at->format('F Y') : '';
$currencyLabel = $user->pricing_region === 'IN' ? 'INR (₹)' : 'USD ($)';
$defaultTab = $errors->has('name') || $errors->has('email') ? 'profile' : ($errors->has('current_password') || $errors->has('password') ? 'security' : 'orders');
@endphp

@section('content')
<div class="st-page max-w-[1120px] mx-auto w-full px-4 sm:px-6 py-12 md:py-16">

    {{-- Header greeting --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-10 border-b border-surface-container-high">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center justify-center w-2 h-2 rounded-full bg-primary-container"></span>
                <span class="font-label-sm text-label-sm uppercase tracking-wider text-secondary">Verified Reader</span>
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight">Namaste, {{ $firstName }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                {{ $maskedEmail }}
                @if($readerSince)
                · Reader since {{ $readerSince }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-4 bg-surface-container-lowest p-3 rounded-DEFAULT border border-surface-container-high shadow-sm">
            <div class="px-3 border-r border-surface-container-high text-left">
                <p class="font-label-sm text-label-sm text-on-surface-variant">Purchased</p>
                <p class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ $guidesCount }} {{ Str::plural('Ebook', $guidesCount) }}</p>
            </div>
            <div class="px-3 border-r border-surface-container-high text-left">
                <p class="font-label-sm text-label-sm text-on-surface-variant">Library files</p>
                <p class="font-headline-sm text-headline-sm text-tertiary font-bold">{{ $filesCount }}</p>
            </div>
            <div class="px-3 text-left">
                <p class="font-label-sm text-label-sm text-on-surface-variant">Currency</p>
                <p class="font-headline-sm text-headline-sm text-on-surface font-bold">{{ $currencyLabel }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-10">
        {{-- Side navigation --}}
        <aside class="lg:col-span-3">
            <nav class="flex lg:flex-col gap-1.5 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0" id="account-nav-tabs">
                <button class="tab-button w-full flex items-center gap-3 px-4 py-3 rounded-DEFAULT font-label-md text-label-md text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-all text-left shrink-0" id="tab-btn-profile" onclick="switchTab('profile')" type="button">
                    <span class="material-symbols-outlined text-[20px]">person_outline</span>
                    <span>Profile Details</span>
                </button>
                <button class="tab-button w-full flex items-center justify-between px-4 py-3 rounded-DEFAULT font-label-md text-label-md text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-all text-left shrink-0" id="tab-btn-orders" onclick="switchTab('orders')" type="button">
                    <span class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                        <span>Orders &amp; Invoices</span>
                    </span>
                    @if($recentOrders->isNotEmpty())
                    <span class="w-2 h-2 rounded-full bg-primary-container shrink-0"></span>
                    @endif
                </button>
                <button class="tab-button w-full flex items-center justify-between px-4 py-3 rounded-DEFAULT font-label-md text-label-md text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-all text-left shrink-0" id="tab-btn-library" onclick="switchTab('library')" type="button">
                    <span class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">menu_book</span>
                        <span>Digital Library</span>
                    </span>
                    <span class="font-label-sm text-label-sm bg-surface-container-high text-secondary px-2 py-0.5 rounded-full">{{ $filesCount }}</span>
                </button>
                <button class="tab-button w-full flex items-center gap-3 px-4 py-3 rounded-DEFAULT font-label-md text-label-md text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-all text-left shrink-0" id="tab-btn-security" onclick="switchTab('security')" type="button">
                    <span class="material-symbols-outlined text-[20px]">lock_reset</span>
                    <span>Security &amp; Password</span>
                </button>
            </nav>
            <div class="mt-8 p-5 bg-surface-container-low rounded-DEFAULT border border-surface-container-high hidden lg:block">
                <div class="flex items-center gap-2 mb-2 text-secondary">
                    <span class="material-symbols-outlined text-[20px]">help_outline</span>
                    <span class="font-headline-sm text-sm font-semibold">Need Assistance?</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant mb-4 leading-relaxed">Questions regarding invoices or digital file downloads?</p>
                <a class="inline-flex items-center gap-1.5 font-label-md text-label-md text-primary font-medium hover:underline" href="{{ route('support.create') }}">
                    <span>Contact Help Desk</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_outward</span>
                </a>
            </div>
        </aside>

        {{-- Panels --}}
        <div class="lg:col-span-9 flex flex-col gap-8">

            {{-- SECTION: Orders & Invoices --}}
            <section class="space-y-6 hidden" id="panel-orders">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-headline-md text-headline-md text-on-surface font-semibold">Orders &amp; Invoices</h2>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Review purchases and open full invoices with payment details.</p>
                    </div>
                    <a href="{{ route('orders.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-DEFAULT bg-surface-container-lowest border border-surface-container-high hover:bg-surface-container-low text-secondary font-label-md text-label-md transition-all shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                        <span>View all orders</span>
                    </a>
                </div>

                @if($recentOrders->isEmpty())
                <div class="bg-surface-container-lowest p-10 rounded-DEFAULT border border-surface-container-high shadow-sm text-center">
                    <div class="w-12 h-12 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                    </div>
                    <p class="font-headline-sm text-headline-sm text-on-surface font-semibold">No orders yet</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">When you buy an ebook, it will appear here.</p>
                    <a href="{{ route('books') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all mt-5">
                        Browse ebooks
                    </a>
                </div>
                @else
                {{-- Desktop table --}}
                <div class="hidden md:block bg-surface-container-lowest rounded-DEFAULT border border-surface-container-high overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/70 border-b border-surface-container-high">
                                <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant">Order</th>
                                <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant">Date</th>
                                <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant">Item</th>
                                <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant text-right">Amount</th>
                                <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant text-center">Status</th>
                                <th class="py-3.5 px-5 font-label-md text-label-md text-on-surface-variant text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-high/60 font-body-sm text-body-sm">
                            @foreach($recentOrders as $order)
                            <tr class="hover:bg-surface/50 transition-colors">
                                <td class="py-4 px-5 font-medium text-on-surface whitespace-nowrap">#{{ $order->order_number }}</td>
                                <td class="py-4 px-5 text-on-surface-variant whitespace-nowrap">{{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y') }}</td>
                                <td class="py-4 px-5">
                                    <span class="font-medium text-on-surface line-clamp-1">{{ $order->items->first()?->title ?? '—' }}</span>
                                    @if($order->items->count() > 1)
                                    <span class="text-on-surface-variant">+{{ $order->items->count() - 1 }} more</span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-right font-medium text-on-surface whitespace-nowrap">{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</td>
                                <td class="py-4 px-5 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-label-sm font-label-sm font-semibold
                                        @if($order->status === 'paid') bg-secondary-container/60 text-secondary
                                        @elseif($order->status === 'pending') bg-primary-fixed/60 text-on-primary-fixed-variant
                                        @else bg-surface-container text-on-surface-variant @endif">
                                        <span class="w-1.5 h-1.5 rounded-full
                                            @if($order->status === 'paid') bg-secondary
                                            @elseif($order->status === 'pending') bg-primary-container
                                            @else bg-on-surface-variant @endif"></span>
                                        {{ $order->statusLabel() }}
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-right whitespace-nowrap">
                                    <a class="inline-flex items-center gap-1 text-primary hover:text-on-primary-container font-label-md text-label-md font-medium hover:underline transition-colors" href="{{ route('orders.show', $order) }}">
                                        <span>View Invoice</span>
                                        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Mobile cards --}}
                <div class="md:hidden space-y-4">
                    @foreach($recentOrders as $order)
                    <div class="bg-surface-container-lowest p-5 rounded-DEFAULT border border-surface-container-high shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-surface-container-high">
                            <div>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">Order #{{ $order->order_number }}</span>
                                <p class="font-body-sm text-body-sm text-on-surface font-medium">{{ $order->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-label-sm font-label-sm font-semibold
                                @if($order->status === 'paid') bg-secondary-container/60 text-secondary
                                @elseif($order->status === 'pending') bg-primary-fixed/60 text-on-primary-fixed-variant
                                @else bg-surface-container text-on-surface-variant @endif">
                                <span class="w-1.5 h-1.5 rounded-full
                                    @if($order->status === 'paid') bg-secondary
                                    @elseif($order->status === 'pending') bg-primary-container
                                    @else bg-on-surface-variant @endif"></span>
                                {{ $order->statusLabel() }}
                            </span>
                        </div>
                        <div>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface line-clamp-1">{{ $order->items->first()?->title ?? '—' }}</h3>
                            <p class="font-label-md text-label-md font-bold text-on-surface mt-0.5">{{ app(\App\Services\PriceService::class)->format((float) $order->grand_total, $order->currency) }}</p>
                        </div>
                        <div class="pt-2">
                            <a class="w-full py-2.5 px-4 rounded-full border border-outline-variant text-on-surface font-label-md text-label-md hover:bg-surface-container-low transition-colors text-center block" href="{{ route('orders.show', $order) }}">
                                View Invoice &amp; Receipt
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <div class="p-4 rounded-DEFAULT bg-surface-container-lowest border border-surface-container-high flex items-start gap-3">
                    <span class="material-symbols-outlined text-[20px] text-secondary mt-0.5">verified_user</span>
                    <div class="text-on-surface-variant font-body-sm text-body-sm">
                        <span class="font-medium text-on-surface">Instant ebook delivery guarantee:</span>
                        every paid order unlocks permanent download links in your library. Invoices include payment verification details.
                    </div>
                </div>
            </section>

            {{-- SECTION: Profile --}}
            <section class="space-y-6 hidden" id="panel-profile">
                <div>
                    <h2 class="font-headline-md text-headline-md text-on-surface font-semibold">Personal Profile</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Update your contact details. Order receipts and ebook delivery go to your email.</p>
                </div>
                <div class="bg-surface-container-lowest p-6 sm:p-8 rounded-DEFAULT border border-surface-container-high shadow-sm">
                    <form method="POST" action="{{ route('account.profile') }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block font-label-md text-label-md text-on-surface font-medium" for="fullName">Full Name</label>
                                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-lowest border border-surface-container-high focus:outline-none focus:border-primary-container text-on-surface font-body-md text-body-md transition-colors" id="fullName" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name"/>
                                @error('name')<p class="font-body-sm text-body-sm text-error font-medium" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-2">
                                <label class="block font-label-md text-label-md text-on-surface font-medium" for="userEmail">Email Address</label>
                                <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-lowest border border-surface-container-high focus:outline-none focus:border-primary-container text-on-surface font-body-md text-body-md transition-colors" id="userEmail" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"/>
                                @error('email')<p class="font-body-sm text-body-sm text-error font-medium" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div class="space-y-2 sm:col-span-2">
                                <div class="flex items-center justify-between">
                                    <label class="block font-label-md text-label-md text-on-surface font-medium" for="countryField">Country / Billing Region</label>
                                    <span class="inline-flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[14px]">lock</span> Locked
                                    </span>
                                </div>
                                <div class="relative">
                                    <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-low border border-surface-container-high text-on-surface-variant/70 font-body-md text-body-md cursor-not-allowed select-none" disabled id="countryField" type="text" value="{{ $user->country_name }} ({{ $user->country_code }})"/>
                                    <div class="absolute right-3.5 top-3.5 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[20px]">public</span>
                                    </div>
                                </div>
                                <p class="font-body-sm text-body-sm text-on-surface-variant/80 flex items-center gap-1.5 mt-1.5">
                                    <span class="material-symbols-outlined text-[16px] text-secondary">info</span>
                                    Country cannot be changed after signup. Contact support if you need it updated.
                                </p>
                            </div>
                        </div>
                        <div class="pt-6 border-t border-surface-container-high flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                Pricing region: <strong class="text-on-surface">{{ $user->pricing_region === 'IN' ? 'India — prices in ₹' : 'International — prices in $' }}</strong>
                            </p>
                            <div class="w-full sm:w-auto ml-auto">
                                <button class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm" type="submit">
                                    Save profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            {{-- SECTION: Digital library --}}
            <section class="hidden space-y-6" id="panel-library">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-headline-md text-headline-md text-on-surface font-semibold">Digital Library</h2>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Your purchased ebooks — open the library for full download controls.</p>
                    </div>
                    <a href="{{ route('library') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-secondary text-on-secondary font-label-md text-label-md font-semibold hover:bg-secondary/90 transition-all shadow-sm shrink-0">
                        <span class="material-symbols-outlined text-[18px]">auto_stories</span>
                        Open Library
                    </a>
                </div>
                @if($libraryItems->isEmpty())
                <div class="bg-surface-container-lowest p-10 rounded-DEFAULT border border-surface-container-high shadow-sm text-center">
                    <div class="w-12 h-12 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-[24px]">menu_book</span>
                    </div>
                    <p class="font-headline-sm text-headline-sm text-on-surface font-semibold">No ebooks yet</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Your purchased ebooks will appear here.</p>
                </div>
                @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($libraryItems as $libItem)
                    <div class="bg-surface-container-lowest p-5 rounded-DEFAULT border border-surface-container-high shadow-sm flex flex-col justify-between">
                        <div class="flex gap-4 mb-4">
                            <div class="w-20 h-28 bg-surface-container rounded-sm overflow-hidden shrink-0 border border-surface-container-high">
                                @if($libItem->book && $libItem->book->displayCoverUrl())
                                <img class="w-full h-full object-cover" src="{{ $libItem->book->displayCoverUrl() }}" alt="Cover of {{ $libItem->title }}" loading="lazy"/>
                                @else
                                <div class="w-full h-full flex items-center justify-center bg-secondary text-on-secondary">
                                    <span class="material-symbols-outlined text-[28px]">menu_book</span>
                                </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                @if($libItem->book && $libItem->book->category)
                                <span class="font-label-sm text-label-sm text-primary font-semibold uppercase tracking-wider">{{ $libItem->book->category->name }}</span>
                                @endif
                                <h3 class="font-headline-sm text-headline-sm text-on-surface mt-1 line-clamp-2">{{ $libItem->title }}</h3>
                                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
                                    @if($libItem->book && $libItem->book->pages){{ $libItem->book->pages }} pages · @endif
                                    Order #{{ $libItem->order->order_number ?? $libItem->order_id }}
                                </p>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-surface-container-high">
                            <a href="{{ route('library') }}" class="w-full py-2 px-3 rounded-full bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md flex items-center justify-center gap-1.5 transition-colors">
                                <span class="material-symbols-outlined text-[18px]">download</span>
                                <span>Download in Library</span>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </section>

            {{-- SECTION: Security --}}
            <section class="hidden space-y-6" id="panel-security">
                <div>
                    <h2 class="font-headline-md text-headline-md text-on-surface font-semibold">Security &amp; Password</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Change your password and manage your personal data.</p>
                </div>
                <div class="bg-surface-container-lowest p-6 sm:p-8 rounded-DEFAULT border border-surface-container-high shadow-sm max-w-xl">
                    <form method="POST" action="{{ route('account.password') }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="space-y-2">
                            <label class="block font-label-md text-label-md text-on-surface font-medium" for="currentPassword">Current Password</label>
                            <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-lowest border border-surface-container-high focus:outline-none focus:border-primary-container text-on-surface font-body-md text-body-md" id="currentPassword" name="current_password" type="password" placeholder="••••••••••••" required autocomplete="current-password"/>
                            @error('current_password')<p class="font-body-sm text-body-sm text-error font-medium" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block font-label-md text-label-md text-on-surface font-medium" for="newPassword">New Password</label>
                            <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-lowest border border-surface-container-high focus:outline-none focus:border-primary-container text-on-surface font-body-md text-body-md" id="newPassword" name="password" type="password" placeholder="Minimum 8 characters" required autocomplete="new-password"/>
                            <p class="font-body-sm text-body-sm text-on-surface-variant/80">Min 8 characters, with upper + lower case, a number and a symbol.</p>
                            @error('password')<p class="font-body-sm text-body-sm text-error font-medium" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block font-label-md text-label-md text-on-surface font-medium" for="confirmPassword">Confirm New Password</label>
                            <input class="w-full h-12 px-4 rounded-DEFAULT bg-surface-container-lowest border border-surface-container-high focus:outline-none focus:border-primary-container text-on-surface font-body-md text-body-md" id="confirmPassword" name="password_confirmation" type="password" placeholder="Repeat the new password" required autocomplete="new-password"/>
                        </div>
                        <div class="pt-4">
                            <button class="inline-flex items-center justify-center px-6 py-2.5 rounded-full bg-secondary text-on-secondary font-label-md text-label-md font-semibold hover:bg-secondary-fixed-dim hover:text-on-secondary-fixed transition-all" type="submit">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-surface-container-lowest p-6 rounded-DEFAULT border border-surface-container-high shadow-sm">
                        <div class="flex items-center gap-2 mb-2 text-secondary">
                            <span class="material-symbols-outlined text-[20px]">download</span>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">My data</h3>
                        </div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Download everything we hold about you — profile, orders, payments and download history — as a JSON file.</p>
                        <a href="{{ route('account.export') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors mt-4">
                            <span class="material-symbols-outlined text-[18px]">file_download</span>
                            Download my data
                        </a>
                    </div>
                    <div class="bg-surface-container-lowest p-6 rounded-DEFAULT border border-surface-container-high shadow-sm">
                        <div class="flex items-center gap-2 mb-2 text-error">
                            <span class="material-symbols-outlined text-[20px]">delete_forever</span>
                            <h3 class="font-headline-sm text-headline-sm text-on-surface font-semibold">Delete my account</h3>
                        </div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Permanently scrub your personal details. Order records are kept for tax purposes, without your name or email.</p>
                        <a href="{{ route('account.delete') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-error-container text-on-error-container font-label-md text-label-md font-semibold hover:opacity-90 transition-all mt-4">
                            Delete my account…
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
(function() {
    var tabs = ['orders', 'profile', 'library', 'security'];

    window.switchTab = function(tabId) {
        tabs.forEach(function(t) {
            var panel = document.getElementById('panel-' + t);
            var btn = document.getElementById('tab-btn-' + t);
            var active = (t === tabId);
            if (panel) {
                if (active) { panel.classList.remove('hidden'); }
                else { panel.classList.add('hidden'); }
            }
            if (btn) {
                if (active) {
                    btn.className = 'tab-button w-full flex items-center justify-between px-4 py-3 rounded-DEFAULT font-label-md text-label-md bg-secondary text-on-secondary shadow-sm transition-all text-left shrink-0';
                } else {
                    btn.className = 'tab-button w-full flex items-center gap-3 px-4 py-3 rounded-DEFAULT font-label-md text-label-md text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-all text-left shrink-0';
                }
            }
        });
        try {
            if (history.replaceState) {
                var url = new URL(window.location.href);
                url.hash = 'tab-' + tabId;
                history.replaceState(null, '', url);
            }
        } catch (e) { /* ignore */ }
    };

    var initial = 'orders';
    try {
        var m = (window.location.hash || '').match(/^#tab-(orders|profile|library|security)$/);
        if (m) { initial = m[1]; }
        else {
            var serverTab = '{{ $defaultTab }}';
            if (tabs.indexOf(serverTab) !== -1) { initial = serverTab; }
        }
    } catch (e) { /* ignore */ }
    window.switchTab(initial);
})();
</script>
@endsection
