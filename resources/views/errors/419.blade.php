@extends('layouts.stitch')

@section('title', 'Session expired — MyEbookBazaar')

@section('content')
@component('errors.partials.shell', [
    'code' => '419',
    'chip' => 'Bookmark Slipped',
    'headline' => 'Your Session Expired',
    'copy' => 'You were inactive for too long. Go back and try again — nothing in your cart is lost.',
])
@endcomponent
@endsection
