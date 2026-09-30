@extends('layouts.stitch')

@section('robots', 'noindex,nofollow')

@section('title', 'Checkout — MyEbookBazaar')

@section('content')
<div class="w-full py-10 px-4 sm:px-6 flex justify-center">
    <div class="bg-surface-container-lowest rounded-DEFAULT p-8 sm:p-10 shadow-sm text-center max-w-lg w-full flex flex-col items-center">
        <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-secondary mb-4">
            <span class="material-symbols-outlined text-[32px]">menu_book</span>
        </div>
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-2">Your basket is empty</h2>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-sm mb-6">Add an ebook to your cart before checking out.</p>
        <a class="inline-flex items-center justify-center h-11 px-6 rounded-full bg-primary-container text-on-primary font-label-md text-label-md font-semibold hover:bg-primary transition-all" href="{{ route('books') }}">
            Browse Catalog
        </a>
    </div>
</div>
@endsection
