<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'MyEbookBazaar — simple, affordable ebooks on fitness, skills and money. Instant download, secure payment.')">

    <title>@yield('title', 'MyEbookBazaar')</title>

    {{-- SEO: Open Graph + Twitter cards + canonical. --}}
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

    @php($seoRobots = trim($__env->yieldContent('robots')))
    @if($seoRobots !== '')
    <meta name="robots" content="{{ $seoRobots }}">
    @endif

    @yield('jsonld')
    <meta name="theme-color" content="#0B3D4A">

    @php($faviconPath = trim((string) settings('site.favicon', '')))
    @if($faviconPath !== '')
    <link rel="icon" href="{{ asset($faviconPath) }}" type="{{ match (strtolower(pathinfo($faviconPath, PATHINFO_EXTENSION))) { 'ico' => 'image/x-icon', 'svg' => 'image/svg+xml', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/png' } }}">
    @endif
    @php($logoPath = trim((string) settings('site.logo', '')))
    @php($logoSrc = $logoPath !== '' ? asset($logoPath) : asset('images/stitch/logo.jpg'))

    {{-- Stitch design system fonts: Bricolage Grotesque (headlines), Work Sans (body), Material Symbols (icons). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="stitch-shell bg-background font-body-md text-body-md text-on-surface antialiased min-h-screen flex flex-col">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[200] focus:bg-primary-container focus:text-on-primary focus:px-4 focus:py-2 focus:rounded-lg focus:font-bold">
        Skip to content
    </a>

    {{-- Announcement bar + header (Stitch design). --}}
    <div class="sticky top-0 left-0 w-full z-50">
        @if(settings_enabled('features.announcement_bar', true))
        @php($annText = trim((string) settings('site.announcement_text', '')))
        @php($annLink = trim((string) safe_url(settings('site.announcement_link', ''))))
        <aside class="w-full bg-inverse-surface text-inverse-on-surface px-4 py-2" id="announcement-bar">
            <div class="max-w-[1120px] mx-auto flex items-center justify-between gap-4">
                <div class="w-6 hidden sm:block"></div>
                <p class="font-label-md text-label-md text-center flex-1">
                    @if($annText !== '')
                        {!! $annText !!}
                        @if($annLink !== '')
                        <a href="{{ $annLink }}" class="underline underline-offset-2 hover:opacity-90 ml-2">{{ $annLink }}</a>
                        @endif
                    @else
                        <span class="text-primary-fixed-dim font-semibold">Special launch offer:</span>
                        20% off all Indian wellness &amp; recipe guides with code
                        <span class="tracking-wider font-semibold text-primary-fixed">WELLNESS20</span>
                    @endif
                </p>
                <button aria-label="Dismiss announcement" class="text-inverse-on-surface/80 hover:text-inverse-on-surface transition-colors" onclick="document.getElementById('announcement-bar').style.display='none';" type="button">
                    <span class="material-symbols-outlined text-[18px] block">close</span>
                </button>
            </div>
        </aside>
        @endif
        <header class="w-full bg-surface-container-lowest border-b border-outline-variant/40">
            <div class="h-16 max-w-[1120px] mx-auto px-4 sm:px-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a class="flex items-center gap-3" href="{{ route('home') }}">
                        <img alt="MyEbookBazaar Logo" class="h-8 w-auto object-contain" src="{{ $logoSrc }}">
                        <span class="font-headline-sm text-headline-sm text-on-surface tracking-tight font-bold">MyEbookBazaar</span>
                    </a>
                </div>
                <nav class="hidden md:flex items-center gap-8">
                    <a class="font-body-md text-body-md transition-colors {{ request()->routeIs('home') ? 'text-primary font-semibold' : 'text-on-surface-variant hover:text-on-surface' }}" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                    <a class="font-body-md text-body-md transition-colors {{ request()->routeIs('books*') ? 'text-primary font-semibold' : 'text-on-surface-variant hover:text-on-surface' }}" href="{{ route('books') }}">Books</a>
                    @if(settings_enabled('features.combos'))
                    <a class="font-body-md text-body-md transition-colors {{ request()->routeIs('combos*', 'offers*') ? 'text-primary font-semibold' : 'text-on-surface-variant hover:text-on-surface' }}" href="{{ route('combos') }}">Combos &amp; Offers</a>
                    @endif
                </nav>
                <div class="flex items-center gap-3">
                    <a aria-label="Search catalog" class="w-9 h-9 rounded-full flex items-center justify-center text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" href="{{ route('books', ['q' => '']) }}#search">
                        <span class="material-symbols-outlined text-[20px]">search</span>
                    </a>
                    <a aria-label="Shopping Cart" class="relative w-9 h-9 rounded-full flex items-center justify-center text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors" href="{{ route('cart.index') }}">
                        <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                        @php($stitchShopper = auth()->user() instanceof \App\Models\User ? auth()->user() : null)
                        @php($cartCount = \App\Models\Cart::forVisitor($stitchShopper, session()->getId())->count())
                        @if($cartCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-primary-container text-on-primary-container font-label-sm text-[10px] font-bold flex items-center justify-center">{{ $cartCount > 9 ? '9+' : $cartCount }}</span>
                        @endif
                    </a>
                    <div class="h-5 w-px bg-outline-variant/50 hidden sm:block mx-1"></div>
                    @auth
                        <a class="hidden sm:inline-flex font-body-sm text-body-sm text-on-surface-variant hover:text-on-surface px-2 py-1 transition-colors" href="{{ route('library') }}">Library</a>
                        <a class="inline-flex items-center justify-center h-9 px-4 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm" href="{{ route('account') }}">Account</a>
                    @else
                        <a class="hidden sm:inline-flex font-body-sm text-body-sm text-on-surface-variant hover:text-on-surface px-2 py-1 transition-colors" href="{{ route('login') }}">Log in</a>
                        <a class="inline-flex items-center justify-center h-9 px-4 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all active:scale-[0.99] shadow-sm" href="{{ route('register') }}">Sign up</a>
                    @endauth
                </div>
            </div>
        </header>
    </div>

    {{-- Flash messages --}}
    {{-- $errors is only shared by the web middleware group; pages rendered
         outside it (e.g. the 503 maintenance response) must not assume it. --}}
    @if(session('success') || session('error') || session('status') || (isset($errors) && $errors->any()))
    <div class="w-full bg-surface">
        <div class="max-w-[1120px] mx-auto px-4 sm:px-6 pt-4 space-y-2">
            @if(session('success'))
            <div class="rounded-DEFAULT bg-surface-container-low border border-outline-variant/40 px-4 py-3 font-body-sm text-body-sm text-on-surface" role="status">{{ session('success') }}</div>
            @endif
            @if(session('error'))
            <div class="rounded-DEFAULT bg-error-container border border-error/30 px-4 py-3 font-body-sm text-body-sm text-on-error-container" role="alert">{{ session('error') }}</div>
            @endif
            @if(session('status'))
            <div class="rounded-DEFAULT bg-surface-container-low border border-outline-variant/40 px-4 py-3 font-body-sm text-body-sm text-on-surface" role="status">{{ session('status') }}</div>
            @endif
            @if(isset($errors) && $errors->any())
            <div class="rounded-DEFAULT bg-error-container border border-error/30 px-4 py-3 font-body-sm text-body-sm text-on-error-container" role="alert">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
    @endif

    <main id="main-content" class="w-full flex-1 bg-surface">
        @yield('content')
    </main>

    {{-- Footer (Stitch design). --}}
    <footer class="w-full bg-inverse-surface text-inverse-on-surface border-t border-outline-variant/20 pt-16 pb-12 mt-auto">
        <div class="max-w-[1120px] mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-outline-variant/20">
                <div class="md:col-span-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="font-headline-md text-headline-md text-inverse-on-surface font-bold">MyEbookBazaar</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-inverse-on-surface/80 max-w-sm mb-6">Simple, practical ebooks for everyday wellness and cooking.</p>
                    <div class="space-y-2 font-body-sm text-body-sm text-inverse-on-surface/70">
                        @php($footerEmail = trim((string) settings('site.support_email', 'support@myebookbazaar.com')))
                        <p class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-tertiary-fixed">mail</span>
                            <a class="hover:text-inverse-on-surface transition-colors" href="mailto:{{ $footerEmail }}">{{ $footerEmail }}</a>
                        </p>
                        @php($footerPhone = trim((string) settings('site.phone', '')))
                        @if($footerPhone !== '')
                        <p class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-tertiary-fixed">call</span>
                            <a class="hover:text-inverse-on-surface transition-colors" href="tel:{{ preg_replace('/\s+/', '', $footerPhone) }}">{{ $footerPhone }}</a>
                        </p>
                        @endif
                        @php($footerAddress = trim((string) settings('site.address', '')))
                        @if($footerAddress !== '')
                        <p class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-tertiary-fixed">location_on</span>
                            <span>{{ $footerAddress }}</span>
                        </p>
                        @endif
                    </div>
                    @php($stitchSocial = array_filter([
                        'facebook' => safe_url(settings('site.facebook')),
                        'instagram' => safe_url(settings('site.instagram')),
                        'x' => safe_url(settings('site.x')),
                        'youtube' => safe_url(settings('site.youtube')),
                    ]))
                    @if(count($stitchSocial))
                    <div class="flex gap-3 mt-4">
                        @foreach($stitchSocial as $network => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}" class="w-9 h-9 rounded-full flex items-center justify-center bg-inverse-on-surface/10 hover:bg-inverse-on-surface/20 text-inverse-on-surface transition-colors">
                            <span class="font-label-sm text-label-sm font-bold">{{ strtoupper(substr($network, 0, 1)) }}</span>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="md:col-span-2 sm:col-span-4">
                    <h4 class="font-label-md text-label-md font-semibold text-inverse-on-surface uppercase tracking-wider mb-4">Shop</h4>
                    <ul class="space-y-2.5 font-body-sm text-body-sm">
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('books') }}">Books</a></li>
                        @if(settings_enabled('features.combos'))
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('combos') }}">Combos</a></li>
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('offers') }}">Offers</a></li>
                        @endif
                    </ul>
                </div>
                <div class="md:col-span-3 sm:col-span-4">
                    <h4 class="font-label-md text-label-md font-semibold text-inverse-on-surface uppercase tracking-wider mb-4">Company</h4>
                    <ul class="space-y-2.5 font-body-sm text-body-sm">
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('about') }}">About Us</a></li>
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('contact') }}">Contact</a></li>
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('faq') }}">FAQ</a></li>
                    </ul>
                </div>
                <div class="md:col-span-3 sm:col-span-4">
                    <h4 class="font-label-md text-label-md font-semibold text-inverse-on-surface uppercase tracking-wider mb-4">Legal</h4>
                    <ul class="space-y-2.5 font-body-sm text-body-sm">
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('privacy') }}">Privacy Policy</a></li>
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('no-refund') }}">No-Refund Policy</a></li>
                        <li><a class="text-inverse-on-surface/75 hover:text-inverse-on-surface transition-colors" href="{{ route('terms') }}">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 font-body-sm text-body-sm text-inverse-on-surface/70">
                <div>{{ settings('site.copyright_text') ?: '© '.date('Y').' MyEbookBazaar. All rights reserved.' }}</div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-tertiary-fixed">lock</span>
                    <span>Secure payments by Razorpay</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Cookie consent (existing component, styled by its own markup). --}}
    <x-cookie-consent />

    {{-- Optional analytics scripts render only after consent (server-side gate). --}}
    @stack('consent-scripts')

    @stack('scripts')
</body>
</html>
