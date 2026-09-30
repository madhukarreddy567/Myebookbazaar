<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'MyEbookBazaar — simple, affordable ebooks on fitness, skills and money. Instant download, secure payment.')">

    <title>@yield('title', 'MyEbookBazaar')</title>

    {{-- SEO: Open Graph + Twitter cards + canonical.
         Pages may override with @section('og_title'), @section('og_description'),
         @section('og_image') or @section('canonical'). --}}
    @php($seoTitle = trim(preg_replace('/\s+/', ' ', strip_tags($__env->yieldContent('title') ?: 'MyEbookBazaar'))))
    @php($seoDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($__env->yieldContent('meta_description') ?: 'MyEbookBazaar — simple, affordable ebooks on fitness, skills and money. Instant download, secure payment.'))), 200))
    @php($seoCanonical = trim($__env->yieldContent('canonical') ?: url()->current()))
    @php($seoImage = trim($__env->yieldContent('og_image') ?: asset('images/stitch/logo.jpg')))
    <meta property="og:site_name" content="MyEbookBazaar">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    {{-- SEO robots override: pages that must not be indexed define
         @section('robots', 'noindex,nofollow'). --}}
    @php($seoRobots = trim($__env->yieldContent('robots')))
    @if($seoRobots !== '')
    <meta name="robots" content="{{ $seoRobots }}">
    @endif

    {{-- Schema.org structured data: pages push JSON-LD with @section('jsonld'). --}}
    @yield('jsonld')
    <meta name="theme-color" content="#0B3D4A">

    {{-- Branding from Settings → General: favicon + logo (fall back to bundled art). --}}
    @php($faviconPath = trim((string) settings('site.favicon', '')))
    @if($faviconPath !== '')
    <link rel="icon" href="{{ asset($faviconPath) }}" type="{{ match (strtolower(pathinfo($faviconPath, PATHINFO_EXTENSION))) { 'ico' => 'image/x-icon', 'svg' => 'image/svg+xml', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/png' } }}">
    @endif
    @php($logoPath = trim((string) settings('site.logo', '')))
    @php($logoSrc = $logoPath !== '' ? asset($logoPath) : asset('images/stitch/logo.jpg'))

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700;9..40,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="st-page antialiased min-h-screen flex flex-col">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[200] focus:bg-[#F5A623] focus:text-[#3A2503] focus:px-4 focus:py-2 focus:rounded-lg focus:font-bold">
        Skip to content
    </a>

    @if(settings_enabled('features.announcement_bar', true) && trim((string) settings('site.announcement_text', '')) !== '')
    <x-ui.announcement-bar id="launch">
        @php($annLink = safe_url(settings('site.announcement_link', '')))
        @if($annLink !== '')
            <a href="{{ $annLink }}">{{ settings('site.announcement_text') }}</a>
        @else
            {{ settings('site.announcement_text') }}
        @endif
    </x-ui.announcement-bar>
    @endif

    {{-- Header — white bar per approved screens --}}
    @php($shopper = auth()->user() instanceof \App\Models\User ? auth()->user() : null)
    @php($cartCount = \App\Models\Cart::forVisitor($shopper, session()->getId())->count())
    @php($visitorRegion = app(\App\Services\PriceService::class)->regionForVisitor($shopper))
    <header class="st-header sticky top-0 z-40"
            x-data="{ drawer: false, userMenu: false, mobileSearch: false }">
        <nav class="max-w-container mx-auto px-4 h-[68px] flex items-center gap-5" aria-label="Main">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0" aria-label="MyEbookBazaar home">
                <img src="{{ $logoSrc }}" alt="" class="w-9 h-9 rounded-xl object-cover" fetchpriority="high">
                <span class="st-brand text-[20px] font-extrabold tracking-tight text-[#101E28]">MyEbookBazaar</span>
            </a>

            {{-- Desktop links --}}
            <div class="hidden lg:flex items-center gap-6">
                <a href="{{ route('home') }}" class="st-navlink" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                <a href="{{ route('books') }}" class="st-navlink" @if(request()->routeIs('books*')) aria-current="page" @endif>Books</a>
                @if(Route::has('combos') && settings_enabled('features.combos', true))
                    <a href="{{ route('combos') }}" class="st-navlink" @if(request()->routeIs('combos*')) aria-current="page" @endif>Combos</a>
                @endif
                @if(Route::has('offers') && settings_enabled('features.sales_offers', true))
                    <a href="{{ route('offers') }}" class="st-navlink" @if(request()->routeIs('offers')) aria-current="page" @endif>Offers</a>
                @endif
                <a href="{{ route('about') }}" class="st-navlink" @if(request()->routeIs('about')) aria-current="page" @endif>About</a>
            </div>

            <div class="flex-1"></div>

            {{-- Search pill (desktop) --}}
            <form method="GET" action="{{ route('books') }}" role="search" class="hidden md:block" aria-label="Search ebooks">
                <div class="st-search-pill">
                    <x-ui.icon name="search" class="w-[18px] h-[18px] text-[#8A98A5] shrink-0" />
                    <label for="nav-search" class="sr-only">Search titles, authors, genres</label>
                    <input id="nav-search" name="q" type="search" value="{{ request('q') }}"
                           placeholder="Search books…" autocomplete="off">
                    <span class="st-kbd" aria-hidden="true">Ctrl K</span>
                </div>
            </form>

            {{-- Pricing region pill (display of the visitor's real region) --}}
            <span class="st-region-pill hidden xl:inline-flex" title="Your pricing region">
                <span class="dot" aria-hidden="true"></span>
                {{ $visitorRegion === 'IN' ? 'INR ₹' : 'INTL $' }}
            </span>

            {{-- Cart --}}
            <a href="{{ route('cart.index') }}" aria-label="Cart{{ $cartCount > 0 ? ', '.$cartCount.' items' : '' }}"
               class="relative p-2 text-[#18262E] hover:text-[#0B3D4A]">
                <x-ui.icon name="cart" class="w-6 h-6" />
                @if($cartCount > 0)
                    <span class="st-cart-badge">{{ $cartCount }}</span>
                @endif
            </a>

            {{-- Auth (desktop) --}}
            @guest
                <div class="hidden md:flex items-center gap-4">
                    <a href="{{ route('login') }}" class="st-login-link">Login</a>
                    <a href="{{ route('register') }}" class="st-btn-register">Register</a>
                </div>
            @else
                <div class="hidden md:block relative">
                    <button @click="userMenu = !userMenu" :aria-expanded="userMenu"
                            class="flex items-center gap-2 p-1.5 pr-2.5 rounded-full hover:bg-[#EDF1F7]">
                        <span class="w-9 h-9 rounded-full bg-[#0B3D4A] text-white flex items-center justify-center font-bold text-sm" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="text-sm font-semibold max-w-[110px] truncate">{{ auth()->user()->name }}</span>
                        <x-ui.icon name="chevron-down" class="w-4 h-4" />
                    </button>
                    <div x-show="userMenu" @click.away="userMenu = false" x-cloak x-transition
                         class="absolute right-0 mt-2 w-56 st-card !rounded-2xl py-1.5 z-50 overflow-hidden">
                        <a href="{{ route('account') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium hover:bg-[#EDF1F7]">
                            <x-ui.icon name="user" class="w-4 h-4" /> My account
                        </a>
                        <a href="{{ route('orders.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium hover:bg-[#EDF1F7]">
                            <x-ui.icon name="file-text" class="w-4 h-4" /> My orders
                        </a>
                        <a href="{{ route('library') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium hover:bg-[#EDF1F7]">
                            <x-ui.icon name="book" class="w-4 h-4" /> My library
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium hover:bg-[#EDF1F7] text-left">
                                <x-ui.icon name="logout" class="w-4 h-4" /> Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endguest

            {{-- Mobile: search toggle + hamburger --}}
            <button type="button" @click="mobileSearch = !mobileSearch" :aria-expanded="mobileSearch"
                    aria-label="Search ebooks" class="md:hidden p-2 text-[#18262E]">
                <x-ui.icon name="search" class="w-6 h-6" />
            </button>
            <button type="button" @click="drawer = true" aria-label="Open menu" class="lg:hidden p-2 -mr-2">
                <x-ui.icon name="menu" class="w-6 h-6" />
            </button>
        </nav>

        {{-- Mobile search panel --}}
        <div x-show="mobileSearch" x-cloak x-transition class="md:hidden px-4 pb-3">
            <form method="GET" action="{{ route('books') }}" role="search">
                <div class="st-search-pill !max-w-none">
                    <x-ui.icon name="search" class="w-[18px] h-[18px] text-[#8A98A5] shrink-0" />
                    <label for="mobile-search" class="sr-only">Search ebooks</label>
                    <input id="mobile-search" name="q" type="search" value="{{ request('q') }}"
                           placeholder="Search books…" autocomplete="off">
                </div>
            </form>
        </div>

        {{-- Mobile drawer --}}
        <div x-show="drawer" x-cloak class="fixed inset-0 z-50 lg:hidden">
            <div class="absolute inset-0 bg-black/40" @click="drawer = false" aria-hidden="true"></div>
            <div class="absolute right-0 top-0 bottom-0 w-72 bg-white shadow-2xl p-6 flex flex-col gap-1"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 role="dialog" aria-label="Menu">
                <div class="flex items-center justify-between mb-4">
                    <span class="flex items-center gap-2">
                        <img src="{{ $logoSrc }}" alt="" class="w-8 h-8 rounded-lg object-cover">
                        <span class="font-extrabold text-xl tracking-tight text-[#101E28]">MyEbookBazaar</span>
                    </span>
                    <button type="button" @click="drawer = false" aria-label="Close menu" class="p-2">
                        <x-ui.icon name="x" class="w-6 h-6" />
                    </button>
                </div>
                <a href="{{ route('home') }}" class="py-3 font-semibold border-b border-[#E4E8F2]">Home</a>
                <a href="{{ route('books') }}" class="py-3 font-semibold border-b border-[#E4E8F2]">Books</a>
                @if(Route::has('combos') && settings_enabled('features.combos', true))
                    <a href="{{ route('combos') }}" class="py-3 font-semibold border-b border-[#E4E8F2]">Combos</a>
                @endif
                @if(Route::has('offers') && settings_enabled('features.sales_offers', true))
                    <a href="{{ route('offers') }}" class="py-3 font-semibold border-b border-[#E4E8F2]">Offers</a>
                @endif
                <a href="{{ route('about') }}" class="py-3 font-semibold border-b border-[#E4E8F2]">About</a>
                <div class="mt-5 flex flex-col gap-2.5">
                    @guest
                        <a href="{{ route('login') }}" class="st-btn st-btn-white st-btn-sm w-full">Login</a>
                        <a href="{{ route('register') }}" class="st-btn st-btn-teal st-btn-sm w-full">Register</a>
                    @else
                        <a href="{{ route('account') }}" class="st-btn st-btn-white st-btn-sm w-full">My account</a>
                        <a href="{{ route('orders.index') }}" class="st-btn st-btn-white st-btn-sm w-full">My orders</a>
                        <a href="{{ route('library') }}" class="st-btn st-btn-white st-btn-sm w-full">My library</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="st-btn st-btn-white st-btn-sm w-full">Logout</button>
                        </form>
                    @endguest
                </div>
            </div>
        </div>
    </header>

    {{-- Toasts --}}
    <div id="toast-stack" class="toast-stack" aria-live="polite">
        @if(session('status'))
            <div class="toast toast-success" role="status">
                <x-ui.icon name="check-circle" class="w-5 h-5" />
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="toast toast-danger" role="alert">
                <x-ui.icon name="alert" class="w-5 h-5" />
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>

    {{-- Page content --}}
    <main id="main-content" class="flex-1">
        @yield('content')
    </main>

    {{-- Footer — cream, dark text (approved screens) --}}
    <footer class="st-footer">
        <div class="max-w-container mx-auto px-4 pt-12 pb-8">
            <div class="grid gap-10 md:grid-cols-[1.5fr_1fr_1fr_1.2fr]">
                <div>
                    <p class="flex items-center gap-2.5">
                        <img src="{{ $logoSrc }}" alt="" class="w-10 h-10 rounded-xl object-cover">
                        <span class="st-brand">MyEbookBazaar</span>
                    </p>
                    <p class="text-sm mt-4 max-w-sm leading-relaxed">
                        {{ settings('site.footer_text') ?: 'A premium literary and technical ebook marketplace engineered for frictionless browsing, instant format delivery, and curated reading journeys worldwide.' }}
                    </p>
                    <div class="flex flex-wrap gap-2.5 mt-5">
                        <span class="st-footer-badge"><x-ui.icon name="shield" class="w-4 h-4 text-[#0B3D4A]" /> Razorpay Verified</span>
                        <span class="st-footer-badge"><x-ui.icon name="lock" class="w-4 h-4 text-[#0B3D4A]" /> Secure checkout</span>
                    </div>
                    @php($socialLinks = array_filter([
                        'facebook' => safe_url(settings('site.facebook')),
                        'instagram' => safe_url(settings('site.instagram')),
                        'x' => safe_url(settings('site.x')),
                        'youtube' => safe_url(settings('site.youtube')),
                    ]))
                    @if(count($socialLinks))
                    <div class="flex gap-2.5 mt-4">
                        @if(!empty($socialLinks['facebook']))
                        <a href="{{ $socialLinks['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook" class="st-footer-badge !p-2.5">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        @endif
                        @if(!empty($socialLinks['instagram']))
                        <a href="{{ $socialLinks['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram" class="st-footer-badge !p-2.5">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.5" fill="currentColor"/></svg>
                        </a>
                        @endif
                        @if(!empty($socialLinks['x']))
                        <a href="{{ $socialLinks['x'] }}" target="_blank" rel="noopener" aria-label="X (Twitter)" class="st-footer-badge !p-2.5">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        @endif
                        @if(!empty($socialLinks['youtube']))
                        <a href="{{ $socialLinks['youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube" class="st-footer-badge !p-2.5">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 7.2s-.2-1.6-.9-2.3c-.9-.9-1.9-.9-2.4-1C16.6 3.6 12 3.6 12 3.6s-4.6 0-7.7.3c-.5.1-1.5.1-2.4 1-.7.7-.9 2.3-.9 2.3S.8 9.1.8 11v1.8c0 1.9.2 3.8.2 3.8s.2 1.6.9 2.3c.9.9 2 .9 2.6 1 1.9.2 7.5.3 7.5.3s4.6 0 7.7-.4c.5-.1 1.5-.1 2.4-1 .7-.7.9-2.3.9-2.3s.2-1.9.2-3.8V11c0-1.9-.2-3.8-.2-3.8zM9.8 15.1V8.4l6.2 3.4-6.2 3.3z"/></svg>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
                <nav aria-label="Quick links">
                    <h4>Quick links</h4>
                    <ul class="space-y-1">
                        <li><a href="{{ route('home') }}" class="st-flink">Home</a></li>
                        <li><a href="{{ route('books') }}" class="st-flink">Books Catalog</a></li>
                        @if(Route::has('combos') && settings_enabled('features.combos', true))
                            <li><a href="{{ route('combos') }}" class="st-flink">Combo Bundles</a></li>
                        @endif
                        @if(Route::has('offers') && settings_enabled('features.sales_offers', true))
                            <li><a href="{{ route('offers') }}" class="st-flink">Offers</a></li>
                        @endif
                    </ul>
                </nav>
                <nav aria-label="Support and policies">
                    <h4>Support &amp; policies</h4>
                    <ul class="space-y-1">
                        <li><a href="{{ route('about') }}" class="st-flink">About Us</a></li>
                        <li><a href="{{ route('privacy') }}" class="st-flink">Privacy Policy</a></li>
                        <li><a href="{{ route('no-refund') }}" class="st-flink">No-Refund Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="st-flink">Terms of Service</a></li>
                        <li><a href="{{ route('faq') }}" class="st-flink">FAQ</a></li>
                    </ul>
                </nav>
                <div>
                    <h4>Get in touch</h4>
                    <p class="text-sm">Have questions about purchases or formats?</p>
                    @php($supportEmail = settings('site.support_email', config('app.support_email', 'support@myebookbazaar.com')))
                    @php($contactPhone = trim((string) settings('site.phone', '')))
                    @php($contactAddress = trim((string) settings('site.address', '')))
                    <p class="mt-2">
                        <a href="mailto:{{ $supportEmail }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#0B3D4A] hover:underline underline-offset-4">
                            <x-ui.icon name="mail" class="w-4 h-4" /> {{ $supportEmail }}
                        </a>
                    </p>
                    @if($contactPhone !== '')
                    <p class="mt-2">
                        <a href="tel:{{ preg_replace('/[^+\d]/', '', $contactPhone) }}" class="text-sm font-bold text-[#0B3D4A] hover:underline underline-offset-4">{{ $contactPhone }}</a>
                    </p>
                    @endif
                    @if($contactAddress !== '')
                    <p class="text-sm mt-2 leading-relaxed">{{ $contactAddress }}</p>
                    @endif
                    <p class="mt-4"><span class="st-footer-badge !bg-[#E9EEF7]">Notice: All sales are final</span></p>
                </div>
            </div>
        </div>
        <div class="st-footer-bottom">
            <div class="max-w-container mx-auto px-4 py-5 flex flex-wrap gap-2 justify-between items-center">
                <span>{{ settings('site.copyright_text') ?: '© '.date('Y').' '.settings('site.name', 'MyEbookBazaar').'. All rights reserved.' }}</span>
                <span class="flex gap-3 items-center">
                    @if(settings_bool('security.cookie_consent_enabled', false))
                        <a href="#" class="hover:underline underline-offset-4"
                           onclick="document.cookie='meb_cookie_consent=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax'; location.reload(); return false;">Cookie settings</a>
                    @endif
                    <span class="inline-flex items-center gap-1.5">Crafted for digital readers</span>
                </span>
            </div>
        </div>
    </footer>

    <x-cookie-consent />

    @stack('scripts')

    {{-- Phase 6: optional tracking scripts render ONLY after cookie consent
         is accepted (server-side gate). Push with @push('consent-scripts'). --}}
    @if(request()->cookie('meb_cookie_consent') === 'accepted')
        @stack('consent-scripts')
    @endif
</body>
</html>
