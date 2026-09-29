@extends('layouts.app')

@section('title', 'Not allowed — MyEbookBazaar')

@section('content')
<div class="error-wrap">
    <div class="stripe-divider mx-auto mb-6" aria-hidden="true"></div>
    <p class="error-code">403</p>
    <h1 class="text-4xl font-extrabold mt-4">You cannot open this page</h1>
    <p class="text-mutedtext mt-3">This area needs a different account. If you believe this is a mistake, contact support.</p>
    <div class="mt-8">
        <x-ui.button href="{{ route('home') }}" size="lg">Go to homepage</x-ui.button>
    </div>
</div>
@endsection
