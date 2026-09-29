<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') — MyEbookBazaar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800 font-body antialiased">

<div class="min-h-screen flex" x-data="{ sidebar: false }">
    {{-- Sidebar --}}
    <aside class="bg-brand-teal-darker text-brand-teal-50 w-64 shrink-0 hidden md:flex flex-col min-h-screen">
        <div class="px-5 py-5">
            <p class="font-heading font-bold text-lg">My<span class="text-brand-orange-light">Ebook</span>Bazaar</p>
            <p class="text-xs text-brand-teal-300 mt-0.5">Admin Panel</p>
        </div>
        <nav class="flex-1 px-3 space-y-1 text-sm">
            @php
                $items = [
                    ['Dashboard', 'reddy.dashboard', false],
                    ['Books', null, true],
                    ['Categories', null, true],
                    ['Orders', null, true],
                    ['Users', null, true],
                    ['Coupons', null, true],
                    ['Banners', null, true],
                    ['Settings', null, true],
                    ['Updates', null, true],
                ];
            @endphp
            @foreach ($items as [$label, $route, $soon])
                @if ($route)
                    <a href="{{ route($route) }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-brand-teal {{ request()->routeIs($route) ? 'bg-brand-teal font-semibold' : '' }}">
                        {{ $label }}
                    </a>
                @else
                    <span class="flex items-center justify-between px-3 py-2.5 rounded-lg text-brand-teal-300 cursor-not-allowed" title="Coming in a later phase">
                        {{ $label }}
                        <span class="text-[10px] uppercase tracking-wide bg-brand-teal text-brand-teal-200 px-2 py-0.5 rounded-full">Soon</span>
                    </span>
                @endif
            @endforeach
        </nav>
        <div class="p-4 border-t border-brand-teal">
            <form method="POST" action="{{ route('reddy.logout') }}">
                @csrf
                <button type="submit" class="w-full text-left text-sm px-3 py-2 rounded-lg hover:bg-brand-teal">Logout ({{ auth('admin')->user()->name }})</button>
            </form>
        </div>
    </aside>

    {{-- Mobile sidebar --}}
    <div x-show="sidebar" x-cloak class="fixed inset-0 z-50 md:hidden">
        <div class="absolute inset-0 bg-black/50" @click="sidebar = false"></div>
        <aside class="absolute left-0 top-0 bottom-0 w-64 bg-brand-teal-darker text-brand-teal-50 flex flex-col">
            <div class="px-5 py-5 flex justify-between items-center">
                <p class="font-heading font-bold">Admin Panel</p>
                <button @click="sidebar = false" class="text-brand-teal-200 text-xl leading-none">&times;</button>
            </div>
            <nav class="flex-1 px-3 space-y-1 text-sm">
                <a href="{{ route('reddy.dashboard') }}" class="block px-3 py-2.5 rounded-lg hover:bg-brand-teal">Dashboard</a>
                @foreach (['Books','Categories','Orders','Users','Coupons','Banners','Settings','Updates'] as $label)
                    <span class="flex items-center justify-between px-3 py-2.5 rounded-lg text-brand-teal-300">{{ $label }} <span class="text-[10px] uppercase bg-brand-teal px-2 py-0.5 rounded-full">Soon</span></span>
                @endforeach
            </nav>
        </aside>
    </div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white shadow-sm px-4 py-3 flex items-center gap-3">
            <button @click="sidebar = true" class="md:hidden text-brand-teal text-xl leading-none">&#9776;</button>
            <h1 class="font-heading font-semibold text-lg text-brand-teal">@yield('page_title', 'Dashboard')</h1>
            <div class="ml-auto">
                <a href="{{ route('reddy.password') }}" class="text-sm text-brand-teal hover:underline">Change Password</a>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @if (session('status'))
                <div class="bg-brand-teal-50 border border-brand-teal-200 text-brand-teal text-sm rounded-xl px-4 py-3 mb-4">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>

        <footer class="bg-white border-t border-gray-200 px-4 py-3 text-xs text-gray-500 flex justify-between">
            <span>MyEbookBazaar Admin</span>
            <span>v{{ config('version.version') }}</span>
        </footer>
    </div>
</div>

</body>
</html>
