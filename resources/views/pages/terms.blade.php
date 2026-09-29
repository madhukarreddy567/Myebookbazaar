@extends('layouts.app')

@section('title', 'Terms of Service — MyEbookBazaar')
@section('meta_description', 'Terms of service of MyEbookBazaar.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Terms of service']]" />

    <div class="grid md:grid-cols-[220px_1fr] gap-8 mt-6">
        <aside class="hidden md:block">
            <nav class="sticky top-24 card p-5" aria-label="On this page">
                <p class="font-bold text-sm mb-3">On this page</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="#use" class="text-teal-700 hover:underline underline-offset-4">Using the store</a></li>
                    <li><a href="#ebooks" class="text-teal-700 hover:underline underline-offset-4">Your ebooks</a></li>
                </ul>
            </nav>
        </aside>

        <x-ui.card class="p-6 md:p-10 prose-page">
            <h1 class="font-extrabold text-4xl md:text-5xl">Terms of service</h1>
            <div class="stripe-divider mt-4 mb-6" aria-hidden="true"></div>

            <h2 id="use">Using the store</h2>
            <p>By using MyEbookBazaar you agree to these terms. You must provide accurate details when creating an account.</p>

            <h2 id="ebooks">Your ebooks</h2>
            <p>Purchased ebooks are for personal use only. Please do not redistribute or resell them.</p>

            <p class="text-mutedtext text-sm mt-8">Full terms coming soon.</p>
        </x-ui.card>
    </div>
</div>
@endsection
