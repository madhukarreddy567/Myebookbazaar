{{--
  Admin layout (D2). Groups/roles scaffold: Phase 5+ adds real routes and roles;
  items with no route render as disabled "Soon" placeholders. No logic changes here.
--}}
@php
$adminUser = auth('admin')->user();
$adminRole = $adminUser->role ?? 'super_admin';

// Configurable brand logo (Settings → General). Empty string = keep the
// original text-only brand mark exactly as designed.
$adminLogo = trim((string) settings('site.logo', ''));
$adminFavicon = trim((string) settings('site.favicon', ''));

$navGroups = [
    'Overview' => [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'reddy.dashboard', 'match' => 'reddy.dashboard'],
    ],
    'Store' => [
        ['label' => 'Books', 'icon' => 'book', 'route' => 'reddy.books.index', 'match' => 'reddy.books*'],
        ['label' => 'Categories', 'icon' => 'tag', 'route' => 'reddy.categories.index', 'match' => 'reddy.categories*'],
        ['label' => 'Combos', 'icon' => 'layers', 'route' => 'reddy.combos.index', 'match' => 'reddy.combos*'],
        ['label' => 'Coupons', 'icon' => 'ticket', 'route' => 'reddy.coupons.index', 'match' => 'reddy.coupons*'],
        ['label' => 'Offers', 'icon' => 'offer', 'route' => 'reddy.offers.index', 'match' => 'reddy.offers*'],
        ['label' => 'Banners', 'icon' => 'image', 'route' => 'reddy.banners.index', 'match' => 'reddy.banners*'],
        ['label' => 'Pages', 'icon' => 'list', 'route' => 'reddy.pages.index', 'match' => 'reddy.pages*'],
    ],
    'Sales' => [
        ['label' => 'Orders', 'icon' => 'cart', 'route' => 'reddy.orders.index', 'match' => 'reddy.orders*'],
        ['label' => 'Reviews', 'icon' => 'list', 'route' => 'reddy.reviews.index', 'match' => 'reddy.reviews*'],
        ['label' => 'Support', 'icon' => 'mail', 'route' => 'reddy.tickets.index', 'match' => 'reddy.tickets*'],
        ['label' => 'Users', 'icon' => 'users', 'route' => 'reddy.users.index', 'match' => 'reddy.users*'],
    ],
    'System' => [
        ['label' => 'Settings', 'icon' => 'settings', 'route' => 'reddy.settings', 'match' => 'reddy.settings*', 'roles' => ['super_admin']],
        ['label' => 'Admins', 'icon' => 'shield', 'route' => 'reddy.admins.index', 'match' => 'reddy.admins*', 'roles' => ['super_admin']],
        ['label' => 'Updates', 'icon' => 'refresh', 'route' => 'reddy.updates.index', 'match' => 'reddy.updates*', 'roles' => ['super_admin']],
        ['label' => 'Backups', 'icon' => 'database', 'route' => 'reddy.backups.index', 'match' => 'reddy.backups*', 'roles' => ['super_admin']],
        ['label' => 'Audit log', 'icon' => 'list', 'route' => 'reddy.audit-log.index', 'match' => 'reddy.audit-log*', 'roles' => ['super_admin']],
        ['label' => 'Security log', 'icon' => 'shield', 'route' => 'reddy.security-log.index', 'match' => 'reddy.security-log*', 'roles' => ['super_admin']],
    ],
    'Account' => [
        ['label' => 'Change password', 'icon' => 'key', 'route' => 'reddy.password', 'match' => 'reddy.password*'],
        ['label' => 'Account security', 'icon' => 'shield', 'route' => 'reddy.account.security', 'match' => 'reddy.account.security*'],
        ['label' => 'Sessions', 'icon' => 'user', 'route' => 'reddy.sessions.index', 'match' => 'reddy.sessions*'],
    ],
];

$canSee = function ($item) use ($adminRole) {
    if (!isset($item['roles'])) return true;
    return in_array('*', $item['roles'], true) || in_array($adminRole, $item['roles'], true);
};
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') — MyEbookBazaar</title>

    {{-- Site favicon (Settings → General → Favicon). --}}
    @if($adminFavicon !== '')
    <link rel="icon" href="{{ asset($adminFavicon) }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <script>
        // Apply saved admin theme before first paint (avoids a flash).
        (function () {
            try {
                document.documentElement.dataset.theme = localStorage.getItem('meb-admin-theme') || 'light';
            } catch (e) { document.documentElement.dataset.theme = 'light'; }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="admin-ui antialiased" x-data="adminShell()" @keydown.escape.window="drawer = false">

<a href="#admin-main" class="sr-only focus:not-sr-only focus:absolute focus:z-[200] focus:bg-white focus:p-3 focus:rounded-lg">
    Skip to content
</a>

<div class="min-h-screen lg:flex">
    {{-- Desktop sidebar --}}
    <aside class="hidden lg:flex w-[260px] shrink-0 flex-col min-h-screen sticky top-0 h-screen overflow-y-auto"
           style="background-color: var(--color-sidebar-bg)">
        <div class="px-5 pt-6 pb-2">
            <p class="{{ $adminLogo !== '' ? 'flex items-center gap-2.5 ' : '' }}font-display font-extrabold text-[22px] leading-none" style="color: var(--color-sidebar-text)">
                {{-- Ternary (not @if/@endif): "@endif" directly abutting "MyEbook" would lex as one --}}
                {{-- unknown directive "@endifMyEbook" and never compile, breaking the layout. --}}
                {!! $adminLogo !== '' ? '<img src="'.e(asset($adminLogo)).'" alt="" class="w-9 h-9 rounded-lg object-cover shrink-0">' : '' !!}MyEbook<span style="color: var(--color-saffron-500)">Bazaar</span>
            </p>
            <p class="text-xs mt-1" style="color: var(--color-sidebar-muted)">Admin panel</p>
        </div>
        <nav class="flex-1 px-3.5 pb-4" aria-label="Admin">
            @foreach ($navGroups as $group => $items)
                @php $visible = array_filter($items, $canSee); @endphp
                @if (count($visible))
                    <p class="side-group-label">{{ $group }}</p>
                    @foreach ($visible as $item)
                        @if (!empty($item['soon']))
                            <span class="side-link disabled" title="Coming in a later phase" aria-disabled="true">
                                <x-admin.icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                                <span class="flex-1">{{ $item['label'] }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                      style="background: rgb(255 255 255 / 0.08); color: var(--color-sidebar-muted)">Soon</span>
                            </span>
                        @else
                            <a href="{{ route($item['route']) }}"
                               class="side-link {{ request()->routeIs($item['match']) ? 'active' : '' }}"
                               @if(request()->routeIs($item['match'])) aria-current="page" @endif>
                                <x-admin.icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </nav>
        <div class="p-4 mx-3.5 mb-4 rounded-xl" style="background: rgb(255 255 255 / 0.06)">
            <p class="text-sm font-semibold truncate" style="color: var(--color-sidebar-text)">{{ $adminUser->name }}</p>
            <p class="text-xs truncate" style="color: var(--color-sidebar-muted)">{{ $adminUser->email }}</p>
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div x-show="drawer" x-cloak class="fixed inset-0 z-[90] lg:hidden" role="dialog" aria-modal="true" aria-label="Admin menu">
        <div class="absolute inset-0 bg-black/50" @click="drawer = false"></div>
        <aside class="absolute left-0 top-0 bottom-0 w-[280px] flex flex-col overflow-y-auto"
               style="background-color: var(--color-sidebar-bg)"
               x-show="drawer" x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <div class="px-5 py-5 flex justify-between items-center">
                <p class="{{ $adminLogo !== '' ? 'flex items-center gap-2 ' : '' }}font-display font-extrabold text-xl" style="color: var(--color-sidebar-text)">
                    {!! $adminLogo !== '' ? '<img src="'.e(asset($adminLogo)).'" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0">' : '' !!}MyEbook<span style="color: var(--color-saffron-500)">Bazaar</span>
                </p>
                <button @click="drawer = false" class="p-2 -m-2" style="color: var(--color-sidebar-muted)" aria-label="Close menu">
                    <x-admin.icon name="x" class="w-6 h-6" />
                </button>
            </div>
            <nav class="flex-1 px-3.5 pb-6" aria-label="Admin">
                @foreach ($navGroups as $group => $items)
                    @php $visible = array_filter($items, $canSee); @endphp
                    @if (count($visible))
                        <p class="side-group-label">{{ $group }}</p>
                        @foreach ($visible as $item)
                            @if (!empty($item['soon']))
                                <span class="side-link disabled"><x-admin.icon :name="$item['icon']" class="w-5 h-5 shrink-0" /><span>{{ $item['label'] }}</span></span>
                            @else
                                <a href="{{ route($item['route']) }}" class="side-link {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                                    <x-admin.icon :name="$item['icon']" class="w-5 h-5 shrink-0" /><span>{{ $item['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </nav>
        </aside>
    </div>

    {{-- Main column --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
        <header class="sticky top-0 z-[80] border-b border-[var(--color-border)]"
                style="background-color: var(--color-surface)">
            <div class="flex items-center gap-2 sm:gap-3 px-4 py-3">
                <button @click="drawer = true" class="lg:hidden p-2 -m-1 text-[var(--color-muted)]" aria-label="Open menu">
                    <x-admin.icon name="menu" class="w-6 h-6" />
                </button>
                <div class="min-w-0">
                    <h1 class="font-display text-xl sm:text-2xl font-bold leading-tight truncate text-[var(--color-text)]">
                        @yield('page_title', 'Dashboard')
                    </h1>
                    @hasSection('breadcrumb')
                        <nav class="text-xs text-[var(--color-muted)] truncate" aria-label="Breadcrumb">@yield('breadcrumb')</nav>
                    @endif
                </div>

                <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
                    {{-- Quick search (global search arrives in a later phase) --}}
                    <div class="relative hidden md:block">
                        <x-admin.icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-muted)] pointer-events-none" />
                        <input type="search" placeholder="Search…" aria-label="Quick search"
                               @keydown.enter.prevent="toast('Global search arrives in a later phase.')"
                               class="input !min-h-[38px] !w-52 !pl-9 !py-1.5 !text-sm">
                    </div>

                    @if (config('meb.maintenance_mode', false))
                        <span class="badge badge-warning" title="Maintenance mode is on">
                            <span class="dot"></span>Maintenance on
                        </span>
                    @endif
                    @if (config('meb.razorpay_test_mode', false))
                        <span class="badge badge-info" title="Razorpay is in test mode">
                            <span class="dot"></span>Razorpay test mode
                        </span>
                    @endif

                    <button @click="toggleTheme()" class="p-2 rounded-lg text-[var(--color-muted)] hover:bg-[var(--color-teal-100)]"
                            :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'">
                        <x-admin.icon name="sun" class="w-5 h-5" x-show="theme === 'dark'" x-cloak />
                        <x-admin.icon name="moon" class="w-5 h-5" x-show="theme !== 'dark'" />
                    </button>

                    {{-- Admin menu --}}
                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-[var(--color-teal-100)]"
                                aria-haspopup="menu" :aria-expanded="open" aria-label="Admin menu">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full font-display font-bold text-lg"
                                  style="background-color: var(--color-teal-700); color: #fff">
                                {{ strtoupper(mb_substr($adminUser->name ?? 'A', 0, 1)) }}
                            </span>
                            <x-admin.icon name="chevron" class="w-4 h-4 text-[var(--color-muted)]" />
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false"
                             x-transition class="absolute right-0 mt-2 w-56 surface p-2 z-[95]" role="menu">
                            <p class="px-3 py-2 text-xs text-[var(--color-muted)] truncate">{{ $adminUser->email }}</p>
                            <a href="{{ route('reddy.password') }}" role="menuitem"
                               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm hover:bg-[var(--color-teal-100)]">
                                <x-admin.icon name="key" class="w-5 h-5" />Profile and password
                            </a>
                            <span class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-[var(--color-muted)] cursor-not-allowed" title="Coming in a later phase">
                                <x-admin.icon name="user" class="w-5 h-5" />Sessions
                            </span>
                            <div class="my-1 border-t border-[var(--color-border)]"></div>
                            <form method="POST" action="{{ route('reddy.logout') }}">
                                @csrf
                                <button type="submit" role="menuitem"
                                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-[var(--color-danger)] hover:bg-[rgb(201_60_60/0.08)]">
                                    <x-admin.icon name="logout" class="w-5 h-5" />Log out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main id="admin-main" class="flex-1 w-full max-w-[1400px] mx-auto p-4 sm:p-6">
            @yield('content')
        </main>

        <footer class="border-t border-[var(--color-border)] px-4 sm:px-6 py-3 text-xs text-[var(--color-muted)] flex justify-between"
                style="background-color: var(--color-surface)">
            <span>MyEbookBazaar admin</span>
            <span>v{{ config('version.version') }}</span>
        </footer>
    </div>
</div>

{{-- Toasts --}}
<div class="fixed bottom-4 right-4 left-4 sm:left-auto z-[110] space-y-3" aria-live="polite">
    @foreach (['status' => 'toast-success', 'success' => 'toast-success', 'warning' => 'toast-warning', 'error' => 'toast-error'] as $key => $cls)
        @if (session($key))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)"
                 x-transition class="toast {{ $cls }}">
                <x-admin.icon :name="in_array($key, ['error', 'warning'], true) ? 'alert' : 'check'" class="w-5 h-5 shrink-0 mt-0.5" />
                <p class="flex-1">{{ session($key) }}</p>
                <button @click="show = false" aria-label="Dismiss"><x-admin.icon name="x" class="w-4 h-4" /></button>
            </div>
        @endif
    @endforeach
    @if ($errors->any())
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition class="toast toast-error">
            <x-admin.icon name="alert" class="w-5 h-5 shrink-0 mt-0.5" />
            <p class="flex-1">{{ $errors->first() }}</p>
            <button @click="show = false" aria-label="Dismiss"><x-admin.icon name="x" class="w-4 h-4" /></button>
        </div>
    @endif
    <template x-for="t in extraToasts" :key="t.id">
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="toast" x-text="t.text"></div>
    </template>
</div>

<x-admin.confirm-modal />

<script>
function adminShell() {
    return {
        drawer: false,
        theme: document.documentElement.dataset.theme || 'light',
        extraToasts: [],
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = this.theme;
            try { localStorage.setItem('meb-admin-theme', this.theme); } catch (e) {}
        },
        toast(text) {
            this.extraToasts.push({ id: Date.now(), text: text });
        }
    };
}
</script>

@stack('scripts')
</body>
</html>
