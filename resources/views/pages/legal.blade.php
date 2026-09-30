{{-- Generic admin-editable legal page (PageController shows this when the admin
     has saved custom legal text in the Settings Center). --}}
@extends('layouts.stitch')

@section('title', $title.' — MyEbookBazaar')

@section('content')
@component('pages.partials.policy-shell', ['active' => ''])
    @slot('title')
        {{ $title }}
    @endslot
    @slot('subtitle')
        The official policy text maintained by the MyEbookBazaar team.
    @endslot

    <section class="bg-surface-container-lowest rounded-DEFAULT p-6 sm:p-8 shadow-sm">
        <div class="max-w-[70ch] font-body-md text-body-md text-on-surface leading-relaxed space-y-4">
            {!! nl2br(e($content)) !!}
        </div>
    </section>
@endcomponent
@endsection
