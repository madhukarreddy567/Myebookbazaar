@extends('layouts.app')

@section('title', 'Page not found — MyEbookBazaar')

@section('content')
<div class="error-wrap">
    <div class="stripe-divider mx-auto mb-6" aria-hidden="true"></div>
    <p class="error-code">404</p>
    <h1 class="text-4xl font-extrabold mt-4">This page does not exist</h1>
    <p class="text-mutedtext mt-3">The link may be broken, or the page may have moved.</p>
    <div class="mt-8">
        <x-ui.button href="{{ route('home') }}" size="lg">Go to homepage</x-ui.button>
    </div>
</div>
@endsection
