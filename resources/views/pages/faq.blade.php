@extends('layouts.app')

@section('title', $page->title.' — MyEbookBazaar')
@section('meta_description', 'Frequently asked questions about buying ebooks on MyEbookBazaar.')

@section('content')
<div class="max-w-container mx-auto px-4 pt-6 pb-10">
    <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => $page->title]]" />

    <h1 class="st-h text-[34px] md:text-[42px] mt-3">{{ $page->title }}</h1>
    <p class="st-muted mt-2 max-w-2xl text-[15px]">Short answers before you buy.</p>

    <div class="st-card px-6 py-4 mt-6 max-w-3xl">
        @if($items)
            <x-ui.accordion :items="$items" />
        @else
            <x-ui.empty-state icon="info" title="No questions yet"
                text="Check back soon — answers are being written." />
        @endif
    </div>
</div>
@endsection
