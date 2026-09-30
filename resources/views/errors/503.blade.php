@extends('layouts.stitch')

@section('title', 'Under maintenance — MyEbookBazaar')

@section('content')
@component('errors.partials.shell', [
    'code' => '503',
    'chip' => 'Routine Upgrade',
    'headline' => 'We are doing quick maintenance',
    'copy' => 'Routine system upgrade in progress to speed up your ebook downloads. All your purchased ebooks remain safe in your library.',
])
    @if(!empty($customMessage ?? ''))
        <p class="font-body-sm text-body-sm text-secondary mt-3 max-w-[340px] mx-auto">{{ $customMessage }}</p>
    @else
        <p class="font-body-sm text-body-sm text-on-surface-variant mt-3 max-w-[340px] mx-auto">The store will be back very soon. Thank you for waiting.</p>
    @endif
@endcomponent
@endsection
