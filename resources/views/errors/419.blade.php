@extends('layouts.app')

@section('title', 'Session expired — MyEbookBazaar')

@section('content')
<div class="error-wrap">
    <div class="stripe-divider mx-auto mb-6" aria-hidden="true"></div>
    <p class="error-code">419</p>
    <h1 class="text-4xl font-extrabold mt-4">Your session expired</h1>
    <p class="text-mutedtext mt-3">You were inactive for too long. Go back and try again.</p>
    <div class="mt-8">
        <x-ui.button href="{{ route('home') }}" size="lg">Go to homepage</x-ui.button>
    </div>
</div>
@endsection
