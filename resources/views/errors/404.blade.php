@extends('layouts.stitch')

@section('title', 'Page not found — MyEbookBazaar')

@section('content')
@component('errors.partials.shell', [
    'code' => '404',
    'chip' => 'Lost in the Stacks',
    'headline' => 'Book Lost in the Stacks',
    'copy' => 'The guide or page you are looking for does not exist, was moved, or is temporarily taking a breather.',
])
@endcomponent
@endsection
