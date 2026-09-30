@extends('layouts.app')

@section('robots', 'noindex,nofollow')

@section('title', 'Delete my account — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'My account', 'url' => route('account')], ['label' => 'Delete account']]" />

    <h1 class="st-h text-[34px] md:text-[42px] mt-3">Delete my account</h1>

    <div class="max-w-2xl mt-6">
        <div class="st-card p-6 md:p-8">
            <div class="rounded-2xl border border-[#F0CACA] bg-[#FDF1F1] p-5 text-sm text-[#7A1F1A]">
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

            <form method="POST" action="{{ route('account.destroy') }}" class="mt-6 flex flex-col gap-4">
                @csrf
                @method('DELETE')

                <div>
                    <label for="password" class="st-label">Confirm with your password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="st-input">
                </div>

                <div>
                    <label for="confirmation" class="st-label">Type <strong>DELETE</strong> to confirm</label>
                    <input id="confirmation" name="confirmation" type="text" required autocomplete="off" class="st-input" placeholder="DELETE">
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="st-btn bg-[#B3372F] !text-white hover:bg-[#8F2B24] !min-h-[52px]">Delete my account forever</button>
                    <a href="{{ route('account') }}" class="st-btn st-btn-teal !min-h-[52px]">Keep my account</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
