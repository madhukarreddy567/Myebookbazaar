@extends('layouts.stitch')

@section('title', 'Something went wrong — MyEbookBazaar')

@section('content')
@component('errors.partials.shell', [
    'code' => '500',
    'chip' => 'Shelf Malfunction',
    'headline' => 'Something Went Wrong',
    'copy' => 'Our team has been notified. Please try again in a little while — your library and orders are safe.',
])
@endcomponent
@endsection
