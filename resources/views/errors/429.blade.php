@extends('layouts.app')

@section('title', 'Too many tries — MyEbookBazaar')

@section('content')
<div class="error-wrap">
    <div class="stripe-divider mx-auto mb-6" aria-hidden="true"></div>
    <p class="error-code">429</p>
    <h1 class="text-4xl font-extrabold mt-4">Too many tries</h1>
    <p class="text-mutedtext mt-3">Please wait a minute, then try again.</p>
    <div class="mt-8">
        <a href="{{ route('home') }}" class="st-btn st-btn-amber">Go to homepage</a>
    </div>
</div>
@endsection
