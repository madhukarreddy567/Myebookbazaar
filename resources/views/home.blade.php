@extends('layouts.app')

@section('title', 'MyEbookBazaar — Affordable Ebooks')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-12">
    {{-- Hero --}}
    <div class="bg-white rounded-3xl shadow-lg p-8 sm:p-12 text-center border border-gray-100">
        <h1 class="font-heading font-bold text-3xl sm:text-5xl text-brand-teal leading-tight">
            Learn anything.<br class="sm:hidden"> <span class="text-brand-orange">Pay less.</span>
        </h1>
        <p class="text-gray-600 mt-4 max-w-xl mx-auto">
            MyEbookBazaar brings you short, practical ebooks on fitness, skills and money —
            written in simple language, priced for everyone.
        </p>
        <a href="{{ route('books') }}" class="inline-block mt-6 bg-brand-teal hover:bg-brand-teal-darker text-white font-semibold px-8 py-3 rounded-full transition">
            Browse Books
        </a>
    </div>

    {{-- Placeholder categories --}}
    <div class="grid sm:grid-cols-3 gap-6 mt-10">
        @foreach (['Fitness' => 'Home workouts and diet guides that fit real life.', 'Skills' => 'Practical skills you can learn step by step.', 'Money' => 'Simple money habits for beginners.'] as $title => $desc)
            <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
                <h2 class="font-heading font-semibold text-lg text-brand-teal">{{ $title }}</h2>
                <p class="text-sm text-gray-600 mt-2">{{ $desc }}</p>
                <p class="text-xs text-brand-orange font-semibold mt-4 uppercase tracking-wide">Coming soon</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
