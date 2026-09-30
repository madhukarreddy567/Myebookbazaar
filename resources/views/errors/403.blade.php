@extends('layouts.stitch')

@section('title', 'Not allowed — MyEbookBazaar')

@section('content')
@component('errors.partials.shell', [
    'code' => '403',
    'chip' => 'Restricted Shelf',
    'headline' => 'You Cannot Open This Page',
    'copy' => 'This area needs a different account. If you believe this is a mistake, contact support.',
])
@endcomponent
@endsection
