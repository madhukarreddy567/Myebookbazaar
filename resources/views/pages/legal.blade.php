@extends('layouts.app')

@section('title', $title.' — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => $title]]" />

    <div class="st-card p-6 md:p-10 st-prose mt-6 max-w-3xl">
        <h1 class="st-h text-[34px] md:text-[42px]">{{ $title }}</h1>
        <div class="stripe-divider mt-4 mb-6" aria-hidden="true"></div>
        {!! nl2br(e($content)) !!}
    </div>
</div>
@endsection
