@extends('layouts.app')

@section('title', 'No-Refund Policy — MyEbookBazaar')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow p-8 border border-gray-100">
        <h1 class="font-heading font-bold text-3xl text-brand-teal">No-Refund Policy</h1>
        <p class="text-gray-600 mt-4">Because our ebooks are delivered instantly as digital downloads, all sales are final and we do not offer refunds.</p>
        <p class="text-gray-600 mt-3">If your download fails for a technical reason, contact us at {{ config('app.support_email', 'support@myebookbazaar.com') }} and we will make it right.</p>
    </div>
</div>
@endsection
