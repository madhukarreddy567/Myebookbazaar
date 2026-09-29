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
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta name="theme-color" content="#0B3D4A">

    {{-- Apply the saved theme before first paint (avoids a flash). --}}
    <script>
    (function () {
        try {
            var t = localStorage.getItem('meb-theme');
            if (t !== 'dark' && t !== 'light') {
                t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.dataset.theme = t;
        } catch (e) {}
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-body antialiased min-h-screen flex flex-col bg-canvas text-inktext">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[200] focus:bg-saffron-500 focus:text-ink-900 focus:px-4 focus:py-2 focus:rounded-lg focus:font-bold">
        Skip to content
    </a>

    @if(settings_enabled('features.announcement_bar', true) && trim((string) settings('site.announcement_text', '')) !== '')
    <x-ui.announcement-bar id="launch">
        @php($annLink = trim((string) settings('site.announcement_link', '')))
        @if($annLink !== '')
            <a href="{{ $annLink }}" class="underline decoration-current underline-offset-2 hover:opacity-80">{{ settings('site.announcement_text') }}</a>
        @else
            {{ settings('site.announcement_text') }}
        @endif
    </x-ui.announcement-bar>
    @endif

    {{-- Navbar --}}
    <header class="sticky top-0 z-40 bg-surface/95 backdrop-blur border-b border-line"
            x-data="{ scrolled: false, drawer: false, userMenu: false, mobileSearch: false }"
            @scroll.window="scrolled = window.scrollY > 8"
            :class="{ 'navbar-slim': scrolled }">
        <nav class="max-w-container mx-auto px-4 h-16 flex items-center gap-0.5 md:gap-1.5 transition-all" aria-label="Main">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0 mr-1" aria-label="MyEbookBazaar home">
                <img src="{{ asset('images/stitch/logo.jpg') }}" alt="" class="w-9 h-9 rounded-xl object-cover shadow-sm" fetchpriority="high">
                <span class="leading-none">
                    <span class="block font-display font-extrabold text-[18px] md:text-[22px] tracking-wide uppercase text-inktext">MyEbook<span class="text-saffron-600">Bazaar</span></span>
                    <span class="hidden min-[420px]:block text-[9px] font-bold tracking-[0.24em] uppercase text-mutedtext mt-0.5">Digital Press</span>
                </span>
            </a>

            {{-- Desktop links --}}
            <div class="hidden md:flex items-center gap-6 ml-5 text-[15px] font-medium">
                <a href="{{ route('home') }}" class="text-inktext hover:text-teal-700 {{ request()->routeIs('home') ? 'font-bold text-teal-700' : '' }}">Home</a>
                <a href="{{ route('books') }}" class="text-inktext hover:text-teal-700 {{ request()->routeIs('books') ? 'font-bold text-teal-700' : '' }}">Books</a>
                @if(Route::has('combos') && settings_enabled('features.combos', true))
                    <a href="{{ route('combos') }}" class="text-inktext hover:text-teal-700 {{ request()->routeIs('combos*') ? 'font-bold text-teal-700' : '' }}">Combos</a>
                @endif
                @if(Route::has('offers') && settings_enabled('features.sales_offers', true))
                    <a href="{{ route('offers') }}" class="text-inktext hover:text-teal-700 {{ request()->routeIs('offers') ? 'font-bold text-teal-700' : '' }}">Offers</a>
                @endif
            </div>

            <div class="flex-1"></div>

            {{-- Search (desktop) --}}
            <form method="GET" action="{{ route('books') }}" role="search" class="hidden md:block w-52">
                <label for="nav-search" class="sr-only">Search ebooks</label>
                <div class="relative">
                    <input id="nav-search" name="q" type="search" value="{{ request('q') }}"
                           placeholder="Search ebooks…" autocomplete="off"
                           class="field-input !min-h-[40px] !py-2 !pr-10 !text-sm">
                    <button type="submit" aria-label="Search"
                            class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-mutedtext hover:text-inktext">
                        <x-ui.icon name="search" class="w-5 h-5" />
                    </button>
                </div>
            </form>

            {{-- Search toggle (mobile) --}}
            <button type="button" @click="mobileSearch = !mobileSearch" :aria-expanded="mobileSearch"
                    aria-label="Search ebooks" class="md:hidden p-2 text-inktext">
                <x-ui.icon name="search" class="w-6 h-6" />
            </button>

            {{-- Cart (badge count computed in-view). Guard: the admin guard can be
                 the default guard (e.g. error pages rendered for staff), and
                 Cart::forVisitor only accepts the customer User model. --}}
            @php($shopper = auth()->user() instanceof \App\Models\User ? auth()->user() : null)
            @php($cartCount = \App\Models\Cart::forVisitor($shopper, session()->getId())->count())
            <a href="{{ route('cart.index') }}" aria-label="Cart{{ $cartCount > 0 ? ', '.$cartCount.' items' : '' }}"
               class="relative p-2 md:p-2.5 text-inktext hover:text-teal-700">
                <x-ui.icon name="cart" class="w-6 h-6" />
                @if($cartCount > 0)
                    <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-saffron-500 text-ink-900 text-[11px] font-bold flex items-center justify-center leading-none">{{ $cartCount }}</span>
                @endif
            </a>

            {{-- Theme toggle --}}
            <button type="button" @click="window.mebTheme.toggle()" aria-label="Toggle dark mode"
                    class="p-2 md:p-2.5 text-inktext hover:text-teal-700"
                    x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
                    @meb:theme.window="dark = ($event.detail === 'dark')">
                <span x-show="!dark" x-cloak><x-ui.icon name="moon" class="w-5 h-5" /></span>
                <span x-show="dark" x-cloak><x-ui.icon name="sun" class="w-5 h-5" /></span>
            </button>

            {{-- Auth (desktop) --}}
            @guest
                <div class="hidden md:flex items-center gap-2">
                    <x-ui.button variant="ghost" size="sm" href="{{ route('login') }}">Login</x-ui.button>
                    <x-ui.button variant="primary" size="sm" href="{{ route('register') }}">Sign up</x-ui.button>
                </div>
            @else
                <div class="hidden md:block relative">
                    <button @click="userMenu = !userMenu" :aria-expanded="userMenu"
                            class="flex items-center gap-2 p-1.5 pr-2.5 rounded-full hover:bg-teal-100">
                        <span class="w-9 h-9 rounded-full bg-ink-900 text-white flex items-center justify-center font-bold text-sm" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="text-sm font-medium max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                        <x-ui.icon name="chevron-down" class="w-4 h-4" />
                    </button>
                    <div x-show="userMenu" @click.away="userMenu = false" x-cloak x-transition
                         class="absolute right-0 mt-2 w-52 card !rounded-xl py-1 z-50 overflow-hidden">
                        <a href="{{ route('account') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-teal-100">
                            <x-ui.icon name="user" class="w-4 h-4" /> My account
                        </a>
                        <a href="{{ route('orders.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-teal-100">
                            <x-ui.icon name="file-text" class="w-4 h-4" /> My orders
                        </a>
                        <a href="{{ route('library') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-teal-100">
                            <x-ui.icon name="book" class="w-4 h-4" /> My library
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-teal-100 text-left">
                                <x-ui.icon name="logout" class="w-4 h-4" /> Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endguest

            {{-- Mobile hamburger --}}
            <button type="button" @click="drawer = true" aria-label="Open menu" class="md:hidden p-2 -mr-2">
                <x-ui.icon name="menu" class="w-6 h-6" />
            </button>
        </nav>

        {{-- Mobile search panel --}}
        <div x-show="mobileSearch" x-cloak x-transition class="md:hidden px-4 pb-3">
            <form method="GET" action="{{ route('books') }}" role="search">
                <label for="mobile-search" class="sr-only">Search ebooks</label>
                <div class="relative">
                    <input id="mobile-search" name="q" type="search" value="{{ request('q') }}"
                           placeholder="Search ebooks…" autocomplete="off"
                           class="field-input !pr-12">
                    <button type="submit" aria-label="Search"
                            class="absolute right-1 top-1/2 -translate-y-1/2 p-2.5 text-mutedtext hover:text-inktext">
                        <x-ui.icon name="search" class="w-5 h-5" />
                    </button>
                </div>
            </form>
        </div>

        {{-- Mobile drawer --}}
        <div x-show="drawer" x-cloak class="fixed inset-0 z-50 md:hidden">
            <div class="absolute inset-0 bg-black/40" @click="drawer = false" aria-hidden="true"></div>
            <div class="absolute right-0 top-0 bottom-0 w-72 bg-surface shadow-raised p-6 flex flex-col gap-2"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 role="dialog" aria-label="Menu">
                <div class="flex items-center justify-between mb-4">
                    <span class="flex items-center gap-2">
                        <img src="{{ asset('images/stitch/logo.jpg') }}" alt="" class="w-8 h-8 rounded-lg object-cover">
                        <span class="font-display font-extrabold text-xl uppercase tracking-wide text-inktext">MyEbookBazaar</span>
                    </span>
                    <button type="button" @click="drawer = false" aria-label="Close menu" class="p-2">
                        <x-ui.icon name="x" class="w-6 h-6" />
                    </button>
                </div>
                <a href="{{ route('home') }}" class="py-3 font-medium border-b border-line">Home</a>
                <a href="{{ route('books') }}" class="py-3 font-medium border-b border-line">Books</a>
                @if(Route::has('combos') && settings_enabled('features.combos', true))
                    <a href="{{ route('combos') }}" class="py-3 font-medium border-b border-line">Combos</a>
                @endif
                @if(Route::has('offers') && settings_enabled('features.sales_offers', true))
                    <a href="{{ route('offers') }}" class="py-3 font-medium border-b border-line">Offers</a>
                @endif
                <div class="mt-4 flex flex-col gap-2">
                    @guest
                        <x-ui.button variant="secondary" href="{{ route('login') }}" class="w-full">Login</x-ui.button>
                        <x-ui.button variant="primary" href="{{ route('register') }}" class="w-full">Sign up</x-ui.button>
                    @else
                        <x-ui.button variant="secondary" href="{{ route('account') }}" class="w-full">My account</x-ui.button>
                        <x-ui.button variant="secondary" href="{{ route('orders.index') }}" class="w-full">My orders</x-ui.button>
                        <x-ui.button variant="secondary" href="{{ route('library') }}" class="w-full">My library</x-ui.button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button variant="ghost" type="submit" class="w-full">Logout</x-ui.button>
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

    {{-- Footer --}}
    <footer class="mt-10 text-white" style="background: var(--color-teal-900)">
        <div class="max-w-container mx-auto px-4 pt-10 pb-6">
            <div class="flex flex-col gap-8 md:grid md:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div>
                    <p class="flex items-center gap-2.5">
                        <img src="{{ asset('images/stitch/logo.jpg') }}" alt="" class="w-10 h-10 rounded-xl object-cover">
                        <span class="leading-none">
                            <span class="block font-display font-extrabold text-2xl uppercase tracking-wide">MyEbookBazaar</span>
                            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[9px] font-bold tracking-[0.18em] uppercase" style="background: var(--color-saffron-500); color: var(--color-teal-900)">Direct PDF</span>
                        </span>
                    </p>
                    <p class="text-sm mt-4 max-w-sm leading-relaxed" style="color: #c8d9dd">
                        Instant digital guides for real results. Short, practical ebooks on
                        fitness, skills and money — written in plain language.
                    </p>
                    <p class="flex items-center gap-2 mt-4 text-sm font-semibold" style="color: #eaf1ff">
                        <x-ui.icon name="shield" class="w-5 h-5" style="color: var(--color-saffron-500)" />
                        Instant PDF download &bull; 100% secure checkout
                    </p>
                    @if(settings('site.instagram') || settings('site.youtube'))
                    <div class="flex flex-col gap-2.5 mt-4">
                        @if(settings('site.instagram'))
                        <a href="{{ settings('site.instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"
                           class="inline-flex items-center gap-2.5 text-sm font-semibold hover:underline underline-offset-4" style="color: #eaf1ff">
                            <span class="p-2 rounded-full" style="background: rgb(255 255 255 / 0.12)">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.5" fill="currentColor"/></svg>
                            </span>
                            {{ '@'.ltrim(basename(rtrim((string) parse_url(settings('site.instagram'), PHP_URL_PATH), '/')), '@') }}
                        </a>
                        @endif
                        @if(settings('site.youtube'))
                        <a href="{{ settings('site.youtube') }}" target="_blank" rel="noopener" aria-label="YouTube — HMKR FILMS"
                           class="inline-flex items-center gap-2.5 text-sm font-semibold hover:underline underline-offset-4" style="color: #eaf1ff">
                            <span class="p-2 rounded-full" style="background: rgb(255 255 255 / 0.12)">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 7.2s-.2-1.6-.9-2.3c-.9-.9-1.9-.9-2.4-1C16.6 3.6 12 3.6 12 3.6s-4.6 0-7.7.3c-.5.1-1.5.1-2.4 1-.7.7-.9 2.3-.9 2.3S.8 9.1.8 11v1.8c0 1.9.2 3.8.2 3.8s.2 1.6.9 2.3c.9.9 2 .9 2.6 1 1.9.2 7.5.3 7.5.3s4.6 0 7.7-.4c.5-.1 1.5-.1 2.4-1 .7-.7.9-2.3.9-2.3s.2-1.9.2-3.8V11c0-1.9-.2-3.8-.2-3.8zM9.8 15.1V8.4l6.2 3.4-6.2 3.3z"/></svg>
                            </span>
                            HMKR FILMS
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
                <nav aria-label="Shop">
                    <p class="font-display font-bold text-lg uppercase tracking-wide mb-3">Shop</p>
                    <ul class="space-y-2.5 text-sm" style="color: #c8d9dd">
                        <li><a href="{{ route('books') }}" class="hover:underline underline-offset-4">All ebooks</a></li>
                        @if(Route::has('combos') && settings_enabled('features.combos', true))
                            <li><a href="{{ route('combos') }}" class="hover:underline underline-offset-4">Combos</a></li>
                        @endif
                        @if(Route::has('offers') && settings_enabled('features.sales_offers', true))
                            <li><a href="{{ route('offers') }}" class="hover:underline underline-offset-4">Offers</a></li>
                        @endif
                    </ul>
                </nav>
                <nav aria-label="Company">
                    <p class="font-display font-bold text-lg uppercase tracking-wide mb-3">Company</p>
                    <ul class="space-y-2.5 text-sm" style="color: #c8d9dd">
                        <li><a href="{{ route('about') }}" class="hover:underline underline-offset-4">About</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:underline underline-offset-4">FAQ</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:underline underline-offset-4">Contact</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:underline underline-offset-4">Privacy policy</a></li>
                        <li><a href="{{ route('no-refund') }}" class="hover:underline underline-offset-4">No-refund policy</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:underline underline-offset-4">Terms of service</a></li>
                    </ul>
                </nav>
                <div>
                    <p class="font-display font-bold text-lg uppercase tracking-wide mb-3">Contact</p>
                    <p class="text-sm" style="color: #c8d9dd">
                        Questions? Email us at<br>
                        <a href="mailto:{{ config('app.support_email', 'support@myebookbazaar.com') }}"
                           class="font-bold hover:underline underline-offset-4" style="color: var(--color-saffron-500)">{{ config('app.support_email', 'support@myebookbazaar.com') }}</a>
                    </p>
                    <p class="flex items-center gap-1.5 mt-4 text-xs" style="color: #9db6bc">
                        <x-ui.icon name="lock" class="w-4 h-4" /> 256-bit SSL secured
                    </p>
                </div>
            </div>
        </div>
        <div style="border-top: 1px solid rgb(255 255 255 / 0.15)">
            <div class="max-w-container mx-auto px-4 py-4 text-xs flex flex-wrap gap-2 justify-between" style="color: #c8d9dd">
                <span>&copy; {{ date('Y') }} MyEbookBazaar. All rights reserved.</span>
                <span class="flex gap-3">
                    @if(settings_bool('security.cookie_consent_enabled', false))
                        <a href="#" class="hover:underline underline-offset-4"
                           onclick="document.cookie='meb_cookie_consent=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax'; location.reload(); return false;">Cookie settings</a>
                    @endif
                    <span>All sales are final. Digital products.</span>
                </span>
            </div>
        </div>
    </footer>

    {{-- Mobile bottom nav --}}
    <nav class="bottom-nav md:hidden" aria-label="Mobile">
        <div class="flex items-stretch justify-around px-2">
            <a href="{{ route('home') }}" class="bottom-nav-link" @if(request()->routeIs('home')) aria-current="page" @endif>
                <x-ui.icon name="home" class="w-6 h-6" />
                <span>Home</span>
            </a>
            <a href="{{ route('books') }}" class="bottom-nav-link" @if(request()->routeIs('books*')) aria-current="page" @endif>
                <x-ui.icon name="book" class="w-6 h-6" />
                <span>Catalog</span>
            </a>
            <a href="{{ route('cart.index') }}" class="bottom-nav-link relative" @if(request()->routeIs('cart*')) aria-current="page" @endif>
                <span class="relative">
                    <x-ui.icon name="cart" class="w-6 h-6" />
                    @if(($cartCount ?? 0) > 0)
                        <span class="absolute -top-1.5 -right-2.5 min-w-[16px] h-4 px-1 rounded-full bg-saffron-500 text-ink-900 text-[10px] font-bold flex items-center justify-center leading-none">{{ $cartCount }}</span>
                    @endif
                </span>
                <span>Cart</span>
            </a>
            <a href="{{ route('library') }}" class="bottom-nav-link" @if(request()->routeIs('library')) aria-current="page" @endif>
                <x-ui.icon name="download" class="w-6 h-6" />
                <span>Library</span>
            </a>
        </div>
    </nav>
    {{-- Spacer so the fixed bottom nav never covers footer content --}}
    <div class="h-[68px] md:hidden" aria-hidden="true"></div>

    <x-cookie-consent />

    @stack('scripts')

    {{-- Phase 6: optional tracking scripts render ONLY after cookie consent
         is accepted (server-side gate). Push with @push('consent-scripts'). --}}
    @if(request()->cookie('meb_cookie_consent') === 'accepted')
        @stack('consent-scripts')
    @endif
</body>
</html>
