{{--
  Friendly "feature disabled" page (Phase 2A). Shown with HTTP 403 when a
  feature switch in the Settings Center is OFF (e.g. registration).
--}}
@extends('layouts.app')

@section('title', $feature.' is unavailable — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 py-16">
    <div class="st-card max-w-xl mx-auto p-8 text-center">
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#FFF0D6]">
            <x-ui.icon name="info" class="w-8 h-8 text-[#B7791F]" />
        </div>
        <h1 class="st-h text-[28px]">{{ $feature }} is currently unavailable</h1>
        <div class="stripe-divider mt-4 mb-6 mx-auto" aria-hidden="true"></div>
        <p class="st-muted">We have temporarily turned this off. Please check back soon, or contact us if you need help.</p>
        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}" class="st-btn st-btn-amber">Back to home</a>
            <a href="{{ route('books') }}" class="st-btn st-btn-teal">Browse ebooks</a>
        </div>
    </div>
</div>
@endsection
