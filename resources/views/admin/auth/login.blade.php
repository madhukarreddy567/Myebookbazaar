<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — MyEbookBazaar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-teal-darker font-body antialiased min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl p-8">
        <p class="font-heading font-bold text-xl text-center text-brand-teal">My<span class="text-brand-orange">Ebook</span>Bazaar</p>
        <p class="text-xs text-center text-gray-500 mt-1 uppercase tracking-widest">Admin Login</p>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mt-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('reddy.login.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}" autocomplete="username" autofocus
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700">
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal-700"> Remember me
            </label>

            <button type="submit" class="w-full bg-brand-orange hover:bg-brand-orange-dark text-white font-semibold py-3 rounded-full transition">
                Login
            </button>
        </form>
    </div>

</body>
</html>
