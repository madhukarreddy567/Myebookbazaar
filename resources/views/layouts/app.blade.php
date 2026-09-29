<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'MyEbookBazaar'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-body antialiased min-h-screen flex flex-col">

    {{-- Navbar --}}
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <nav class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="w-9 h-9 rounded-xl bg-brand-teal flex items-center justify-center text-white font-heading font-bold text-lg">M</span>
                <span class="font-heading font-bold text-xl text-brand-teal">My<span class="text-brand-orange">Ebook</span>Bazaar</span>
            </a>

            <div class="hidden sm:flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-brand-teal">Home</a>
                <a href="{{ route('books') }}" class="text-gray-700 hover:text-brand-teal">Books</a>
            </div>

            <div class="flex items-center gap-2">
                @guest
                    <a href="{{ route('login') }}" class="text-sm font-medium text-brand-teal hover:underline px-3 py-2">Login</a>
                    <a href="{{ route('register') }}" class="text-sm font-semibold bg-brand-orange hover:bg-brand-orange-dark text-white px-4 py-2 rounded-full transition">Register</a>
                @else
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-brand-teal px-3 py-2 rounded-full hover:bg-gray-100">
                            <span class="w-8 h-8 rounded-full bg-brand-teal text-white flex items-center justify-center font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50">
                            <a href="{{ route('account') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">My Account</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Logout</button>
                            </form>
                        </div>
                    </div>
                @endguest
                {{-- Mobile nav --}}
                <div class="sm:hidden flex items-center gap-3 text-sm font-medium">
                    <a href="{{ route('home') }}" class="text-gray-700">Home</a>
                    <a href="{{ route('books') }}" class="text-gray-700">Books</a>
                </div>
            </div>
        </nav>
    </header>

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="max-w-6xl mx-auto px-4 mt-4 w-full">
            <div class="bg-brand-teal-50 border border-brand-teal-200 text-brand-teal text-sm rounded-xl px-4 py-3">{{ session('status') }}</div>
        </div>
    @endif

    {{-- Page content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-brand-teal text-brand-teal-50 mt-12">
        <div class="max-w-6xl mx-auto px-4 py-10 grid gap-8 sm:grid-cols-3">
            <div>
                <p class="font-heading font-bold text-lg">My<span class="text-brand-orange-light">Ebook</span>Bazaar</p>
                <p class="text-sm text-brand-teal-100 mt-2">Simple, affordable ebooks for fitness, skills and money — made for India and the world.</p>
            </div>
            <div>
                <p class="font-heading font-semibold mb-3">Company</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="hover:text-brand-orange-light">About</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-brand-orange-light">Privacy Policy</a></li>
                    <li><a href="{{ route('no-refund') }}" class="hover:text-brand-orange-light">No-Refund Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-brand-orange-light">Terms of Service</a></li>
                </ul>
            </div>
            <div>
                <p class="font-heading font-semibold mb-3">Contact</p>
                <p class="text-sm text-brand-teal-100">Questions? Email us at<br>
                    <a href="mailto:{{ config('app.support_email', 'support@myebookbazaar.com') }}" class="text-brand-orange-light hover:underline">{{ config('app.support_email', 'support@myebookbazaar.com') }}</a>
                </p>
            </div>
        </div>
        <div class="border-t border-brand-teal-700">
            <div class="max-w-6xl mx-auto px-4 py-4 text-xs text-brand-teal-200 flex justify-between">
                <span>&copy; {{ date('Y') }} MyEbookBazaar. All rights reserved.</span>
            </div>
        </div>
    </footer>
</body>
</html>
