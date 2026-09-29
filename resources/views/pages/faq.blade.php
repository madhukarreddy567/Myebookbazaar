@extends('layouts.app')

@section('title', $page->title.' — MyEbookBazaar')
@section('meta_description', 'Frequently asked questions about buying ebooks on MyEbookBazaar.')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => $page->title]]" />

    <h1 class="display-lg text-teal-900 uppercase mt-3">{{ $page->title }}</h1>
    <p class="text-mutedtext mt-2 max-w-2xl">Short answers before you buy.</p>

    <x-ui.card class="px-6 py-2 mt-6 max-w-3xl">
        @if($items)
            <x-ui.accordion :items="$items" />
        @else
            <x-ui.empty-state icon="info" title="No questions yet"
                text="Check back soon — answers are being written." />
        @endif
    </x-ui.card>
</div>
@endsection
