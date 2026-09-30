@extends('layouts.app')

@section('title', 'Something went wrong — MyEbookBazaar')

@section('content')
<div class="error-wrap">
    <div class="stripe-divider mx-auto mb-6" aria-hidden="true"></div>
    <p class="error-code">500</p>
    <h1 class="text-4xl font-extrabold mt-4">Something went wrong</h1>
    <p class="text-mutedtext mt-3">Our team has been notified. Please try again in a little while.</p>
    <div class="mt-8">
        <a href="{{ route('home') }}" class="st-btn st-btn-amber">Go to homepage</a>
    </div>
</div>
@endsection
