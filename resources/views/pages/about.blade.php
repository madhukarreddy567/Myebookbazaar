@extends('layouts.app')

@section('title', $page->title.' — MyEbookBazaar')
@section('meta_description', 'About MyEbookBazaar: simple, affordable ebooks on fitness, skills and money.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'About']]" />

    <div class="grid md:grid-cols-[1fr_320px] gap-8 mt-6">
        <x-ui.card class="p-6 md:p-10 prose-page">
            @if(trim($page->content) !== '')
                <h1 class="font-extrabold text-4xl md:text-5xl">{{ $page->title }}</h1>
                <div class="stripe-divider mt-4 mb-6" aria-hidden="true"></div>
                {!! nl2br(e($page->content)) !!}
            @else
            <h1 class="font-extrabold text-4xl md:text-5xl">About MyEbookBazaar</h1>
            <div class="stripe-divider mt-4 mb-6" aria-hidden="true"></div>
            <p>MyEbookBazaar is an online bookstore selling short, practical ebooks on fitness, skills and money — written in simple language and priced honestly.</p>
            <h2>What we believe</h2>
            <ul>
                <li>Good guidance should be short enough to finish and clear enough to act on.</li>
                <li>Advice should fit real life — Indian kitchens, home workouts, small budgets.</li>
                <li>Prices should be honest: rupees for India, dollars for the rest of the world.</li>
            </ul>
            <h2>What you get</h2>
            <p>Every ebook is a PDF that works on any phone, tablet or computer. You pay once through secure Razorpay checkout and download instantly — no subscription, no waiting.</p>
            @endif
        </x-ui.card>

        <aside>
            <x-ui.card class="p-6 md:sticky md:top-24">
                <h2 class="text-xl font-bold mb-3">Why readers choose us</h2>
                <ul class="space-y-3 text-sm">
                    <li class="flex gap-2"><x-ui.icon name="check-circle" class="w-5 h-5 text-success shrink-0" /> Instant download after payment</li>
                    <li class="flex gap-2"><x-ui.icon name="check-circle" class="w-5 h-5 text-success shrink-0" /> Made for Indian kitchens</li>
                    <li class="flex gap-2"><x-ui.icon name="check-circle" class="w-5 h-5 text-success shrink-0" /> Secure Razorpay payment</li>
                    <li class="flex gap-2"><x-ui.icon name="check-circle" class="w-5 h-5 text-success shrink-0" /> Simple, plain language</li>
                </ul>
                <div class="mt-6">
                    <x-ui.button href="{{ route('books') }}" class="w-full">Browse ebooks</x-ui.button>
                </div>
            </x-ui.card>
        </aside>
    </div>
</div>
@endsection
