@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="bg-white rounded-2xl shadow p-8 border border-gray-100">
    <h2 class="font-heading font-semibold text-xl text-brand-teal">Welcome, {{ auth('admin')->user()->name }}</h2>
    <p class="text-sm text-gray-600 mt-2">
        This dashboard is intentionally empty in Phase 1. Books, Categories, Orders, Users,
        Coupons, Banners, Settings and Updates arrive in later phases.
    </p>

    <div class="grid sm:grid-cols-3 gap-4 mt-6">
        @foreach ([['Countries seeded', \App\Models\Country::count()], ['Customers', \App\Models\User::count()], ['Admins', \App\Models\Admin::count()]] as [$label, $count])
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <p class="text-2xl font-heading font-bold text-brand-teal">{{ $count }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
