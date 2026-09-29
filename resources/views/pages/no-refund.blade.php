@extends('layouts.app')

@section('title', 'No-Refund Policy — MyEbookBazaar')
@section('meta_description', 'No-refund policy of MyEbookBazaar: all sales are final because ebooks are digital products.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'No-refund policy']]" />

    <div class="grid md:grid-cols-[220px_1fr] gap-8 mt-6">
        <aside class="hidden md:block">
            <nav class="sticky top-24 card p-5" aria-label="On this page">
                <p class="font-bold text-sm mb-3">On this page</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="#final" class="text-teal-700 hover:underline underline-offset-4">All sales are final</a></li>
                    <li><a href="#help" class="text-teal-700 hover:underline underline-offset-4">Download problems</a></li>
                </ul>
            </nav>
        </aside>

        <x-ui.card class="p-6 md:p-10 prose-page">
            <h1 class="font-extrabold text-4xl md:text-5xl">No-refund policy</h1>
            <div class="stripe-divider mt-4 mb-6" aria-hidden="true"></div>

            <x-ui.alert type="warning" class="mb-6">
                <strong>All sales are final.</strong>&nbsp;Please read the book description carefully before you pay.
            </x-ui.alert>

            <h2 id="final">All sales are final</h2>
            <p>Because our ebooks are delivered instantly as digital downloads, all sales are final and we do not offer refunds.</p>

            <h2 id="help">Download problems</h2>
            <p>If your download fails for a technical reason, contact us at {{ config('app.support_email', 'support@myebookbazaar.com') }} and we will make it right.</p>
        </x-ui.card>
    </div>
</div>
@endsection
