@extends('layouts.stitch')

@section('title', 'Too many tries — MyEbookBazaar')

@section('content')
@component('errors.partials.shell', [
    'code' => '429',
    'chip' => 'Slow Down, Reader',
    'headline' => 'Too Many Tries',
    'copy' => 'Please wait a minute, then try again. This keeps the store safe for everyone.',
])
@endcomponent
@endsection
