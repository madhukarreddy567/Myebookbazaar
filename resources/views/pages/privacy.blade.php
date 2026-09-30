@extends('layouts.app')

@section('title', 'Privacy Policy — MyEbookBazaar')
@section('meta_description', 'Privacy policy of MyEbookBazaar: what data we collect and how we use it.')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Privacy policy']]" />

    <div class="grid md:grid-cols-[220px_1fr] gap-8 mt-6">
        <aside class="hidden md:block">
            <nav class="sticky top-24 st-card p-5" aria-label="On this page">
                <p class="font-bold text-sm mb-3">On this page</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="#collect" class="text-[#0B3D4A] hover:underline underline-offset-4 font-semibold">What we collect</a></li>
                    <li><a href="#use" class="text-[#0B3D4A] hover:underline underline-offset-4 font-semibold">How we use it</a></li>
                    <li><a href="#share" class="text-[#0B3D4A] hover:underline underline-offset-4 font-semibold">What we never do</a></li>
                </ul>
            </nav>
        </aside>

        <div class="st-card p-6 md:p-10 st-prose">
            <h1 class="st-h text-[34px] md:text-[42px]">Privacy policy</h1>
            <div class="stripe-divider mt-4 mb-6" aria-hidden="true"></div>

            <h2 id="collect">What we collect</h2>
            <p>We collect only what we need to run your account and deliver your ebooks: your name, email and country.</p>

            <h2 id="use">How we use it</h2>
            <p>Your details are used to create your account, process your orders, deliver downloads and send important emails like order receipts and password resets.</p>

            <h2 id="share">What we never do</h2>
            <p>We never sell your data. Payment details are handled securely by Razorpay and never touch our servers.</p>

            <p class="st-muted text-sm mt-8">Full policy coming soon.</p>
        </div>
    </div>
</div>
@endsection
