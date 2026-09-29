@extends('layouts.app')

@section('title', 'Contact us — MyEbookBazaar')
@section('meta_description', 'Contact MyEbookBazaar support.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Contact']]" />

    <div class="max-w-2xl">
        <h1 class="display-lg text-teal-900 uppercase mt-3">Contact us</h1>
        <p class="text-mutedtext mt-2">Questions about an ebook, an order or a download? Write to us — we reply within 2 working days.</p>

        @if(session('status'))
            <x-ui.alert type="success" class="mt-4">{{ session('status') }}</x-ui.alert>
        @endif

        <x-ui.card class="p-6 mt-6">
            <form method="POST" action="{{ route('contact.send') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="c-name" class="field-label">Your name</label>
                    <input id="c-name" name="name" type="text" value="{{ old('name') }}" required maxlength="100"
                           class="field-input" autocomplete="name">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="c-email" class="field-label">Email</label>
                    <input id="c-email" name="email" type="email" value="{{ old('email') }}" required maxlength="255"
                           class="field-input" autocomplete="email">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="c-subject" class="field-label">Subject <span class="text-mutedtext font-normal">(optional)</span></label>
                    <input id="c-subject" name="subject" type="text" value="{{ old('subject') }}" maxlength="150"
                           class="field-input">
                    @error('subject')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="c-message" class="field-label">Message</label>
                    <textarea id="c-message" name="message" rows="6" required maxlength="5000"
                              class="field-input">{{ old('message') }}</textarea>
                    @error('message')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <x-security.honeypot />

                <x-turnstile form="contact" />

                <x-ui.button type="submit" size="lg" class="w-full sm:w-auto">Send message</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
