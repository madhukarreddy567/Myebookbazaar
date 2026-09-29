@extends('layouts.app')

@section('title', 'Books — MyEbookBazaar')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-12">
    <h1 class="font-heading font-bold text-3xl text-brand-teal">Books</h1>
    <p class="text-gray-600 mt-2">Our catalogue is being prepared. Check back soon!</p>

    <div class="bg-white rounded-2xl shadow p-10 mt-8 text-center border border-gray-100">
        <p class="text-gray-500">New titles are on the way — fitness, skills and money guides at honest prices.</p>
        @guest
            <a href="{{ route('register') }}" class="inline-block mt-6 bg-brand-orange hover:bg-brand-orange-dark text-white font-semibold px-6 py-2.5 rounded-full transition">Create a free account</a>
        @endguest
    </div>
</div>
@endsection
