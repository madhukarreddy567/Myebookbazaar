@extends('layouts.app')

@section('title', 'Under maintenance — MyEbookBazaar')

@section('content')
<div class="error-wrap">
    <div class="stripe-divider mx-auto mb-6" aria-hidden="true"></div>
    <p class="error-code">503</p>
    <h1 class="text-4xl font-extrabold mt-4">We are doing quick maintenance</h1>
    {{-- Phase 6: optional custom message from Settings → Security. --}}
    @if(!empty($customMessage ?? ''))
        <p class="text-mutedtext mt-3">{{ $customMessage }}</p>
    @else
        <p class="text-mutedtext mt-3">The store will be back very soon. Thank you for waiting.</p>
    @endif
    <div class="mt-8">
        <a href="{{ route('home') }}" class="st-btn st-btn-amber">Try again</a>
    </div>
</div>
@endsection
