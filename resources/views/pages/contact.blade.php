@extends('layouts.app')

@section('title', 'Contact us — MyEbookBazaar')
@section('meta_description', 'Contact MyEbookBazaar support.')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Contact']]" />

    <div class="max-w-2xl">
        <h1 class="st-h text-[34px] md:text-[42px] mt-3">Contact us</h1>
        <p class="st-muted mt-2 text-[15px]">Questions about an ebook, an order or a download? Write to us — we reply within 2 working days.</p>

        @if(session('status'))
            <x-ui.alert type="success" class="mt-4">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="st-card p-6 mt-6">
            <form method="POST" action="{{ route('contact.send') }}" class="flex flex-col gap-4">
                @csrf
                <div>
                    <label for="c-name" class="st-label">Your name</label>
                    <input id="c-name" name="name" type="text" value="{{ old('name') }}" required maxlength="100"
                           class="st-input" autocomplete="name">
                    @error('name')<p class="st-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="c-email" class="st-label">Email</label>
                    <input id="c-email" name="email" type="email" value="{{ old('email') }}" required maxlength="255"
                           class="st-input" autocomplete="email">
                    @error('email')<p class="st-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="c-subject" class="st-label">Subject <span class="st-hint font-normal">(optional)</span></label>
                    <input id="c-subject" name="subject" type="text" value="{{ old('subject') }}" maxlength="150"
                           class="st-input">
                    @error('subject')<p class="st-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="c-message" class="st-label">Message</label>
                    <textarea id="c-message" name="message" rows="6" required maxlength="5000"
                              class="st-input">{{ old('message') }}</textarea>
                    @error('message')<p class="st-field-error">{{ $message }}</p>@enderror
                </div>
                <x-security.honeypot />

                <x-turnstile form="contact" />

                <button type="submit" class="st-btn st-btn-teal !min-h-[54px] !text-[16px] w-full sm:w-auto">Send message</button>
            </form>
        </div>
    </div>
</div>
@endsection
