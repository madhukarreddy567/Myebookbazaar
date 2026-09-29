<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Two-factor authentication — MyEbookBazaar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen flex items-center justify-center px-4 py-10 relative overflow-hidden"
      style="background-color: var(--color-teal-900)">

    {{-- Diagonal stripe motif, low contrast, CSS only --}}
    <div class="stripes pointer-events-none absolute -right-24 -top-24 h-[420px] w-[420px] opacity-[0.07] rotate-12" aria-hidden="true"></div>
    <div class="stripes pointer-events-none absolute -left-28 -bottom-28 h-[460px] w-[460px] opacity-[0.05] -rotate-12" aria-hidden="true"></div>

    <div class="w-full max-w-sm relative">
        <p class="font-display font-extrabold text-3xl text-center" style="color: #E8F1F2">
            MyEbook<span style="color: var(--color-saffron-500)">Bazaar</span>
        </p>
        <p class="text-xs text-center mt-2 tracking-[0.2em]" style="color: var(--color-sidebar-muted)">Two-factor authentication</p>

        <div class="surface mt-6 p-8">
            <p class="text-sm text-[var(--color-muted)] mb-5">
                Enter the 6-digit code from your authenticator app. Lost your phone?
                Use one of your recovery codes instead.
            </p>

            @if ($errors->any())
                <div class="flex items-start gap-2.5 rounded-xl border border-[rgb(201_60_60/0.3)] bg-[rgb(201_60_60/0.08)] text-[var(--color-danger)] text-sm px-4 py-3 mb-5" role="alert">
                    <x-admin.icon name="alert" class="w-5 h-5 shrink-0" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('reddy.2fa.challenge.verify') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="code" class="field-label">Authentication code</label>
                    <input id="code" name="code" type="text" required autofocus
                           inputmode="numeric" autocomplete="one-time-code"
                           placeholder="123456" maxlength="32" class="input text-center tracking-[0.3em] text-lg">
                </div>

                <button type="submit" class="btn btn-primary w-full !min-h-[48px] !text-base">
                    Verify &amp; sign in
                </button>
            </form>

            <p class="mt-4 text-center">
                <a href="{{ route('reddy.login') }}" class="text-sm text-[var(--color-muted)] hover:underline">
                    Use a different account
                </a>
            </p>
        </div>

        <p class="text-center text-xs mt-6" style="color: var(--color-sidebar-muted)">
            Restricted area. Unauthorized access is prohibited.
        </p>
    </div>

</body>
</html>
