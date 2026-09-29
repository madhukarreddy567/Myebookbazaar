@extends('layouts.app')

@section('title', 'Delete my account — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My account', 'url' => route('account')], ['label' => 'Delete account']]" />

    <h1 class="text-5xl font-extrabold mt-4">Delete my account</h1>

    <div class="max-w-2xl mt-6">
        <x-ui.card class="p-6 md:p-8">
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                <p class="font-bold">This cannot be undone.</p>
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    <li>Your name, email and password will be permanently scrubbed.</li>
                    <li>You will be signed out immediately and won't be able to sign in again.</li>
                    <li>Your download access ends — download your ebooks first.</li>
                    <li>Your <strong>order and payment records are kept</strong> for tax and accounting, as required by law — but with your personal details removed.</li>
                </ul>
            </div>

            @if ($errors->any())
                <x-ui.alert type="error" class="mt-4">{{ $errors->first() }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('account.destroy') }}" class="mt-6 space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label for="password" class="field-label">Confirm with your password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="field-input">
                </div>

                <div>
                    <label for="confirmation" class="field-label">Type <strong>DELETE</strong> to confirm</label>
                    <input id="confirmation" name="confirmation" type="text" required autocomplete="off" class="field-input" placeholder="DELETE">
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit" variant="danger">Delete my account forever</x-ui.button>
                    <x-ui.button href="{{ route('account') }}" variant="ghost">Keep my account</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
