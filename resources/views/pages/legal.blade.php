@extends('layouts.app')

@section('title', $title.' — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => $title]]" />

    <x-ui.card class="p-6 md:p-10 prose-page mt-6 max-w-3xl">
        <h1 class="font-extrabold text-4xl md:text-5xl">{{ $title }}</h1>
        <div class="stripe-divider mt-4 mb-6" aria-hidden="true"></div>
        {!! nl2br(e($content)) !!}
    </x-ui.card>
</div>
@endsection
