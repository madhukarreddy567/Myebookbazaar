@extends('layouts.app')

@section('title', 'Checkout — MyEbookBazaar')

@section('content')
<div class="max-w-container mx-auto px-4 py-10">
    <x-ui.card class="max-w-lg mx-auto p-8 text-center">
        <x-ui.empty-state icon="cart" title="Your cart is empty"
            text="Add an ebook to your cart before checking out."
            :action-url="route('books')" action-label="Browse ebooks" />
    </x-ui.card>
</div>
@endsection
