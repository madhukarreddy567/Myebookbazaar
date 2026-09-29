@extends('layouts.app')

@section('title', 'Create Account — MyEbookBazaar')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-lg p-8 border border-gray-100">
        <h1 class="font-heading font-bold text-2xl text-brand-teal text-center">Create your account</h1>
        <p class="text-sm text-gray-500 text-center mt-1">Free forever. Buy ebooks in one click later.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" x-data="countryPicker()">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}" autocomplete="name"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('name') border-red-500 @enderror">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}" autocomplete="email"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('email') border-red-500 @enderror">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Searchable country dropdown (required). --}}
            <div class="relative">
                <label for="country_search" class="block text-sm font-medium text-gray-700">Country</label>
                <input id="country_search" type="text" autocomplete="off" placeholder="Type to search countries…"
                       x-model="query" @focus="open = true" @input="open = true"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('country_code') border-red-500 @enderror">
                <input type="hidden" name="country_code" :value="selected">
                <div x-show="open" @click.away="open = false" x-cloak
                     class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg">
                    <template x-for="c in filtered()" :key="c.code">
                        <button type="button" @click="choose(c)"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-brand-teal-50 flex justify-between">
                            <span x-text="c.name"></span>
                            <span class="text-gray-400" x-text="c.phone"></span>
                        </button>
                    </template>
                    <p x-show="filtered().length === 0" class="px-4 py-3 text-sm text-gray-500">No country found.</p>
                </div>
                @error('country_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-500 mt-1">Your country sets your pricing region and cannot be changed later.</p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700 @error('password') border-red-500 @enderror">
                <p class="text-xs text-gray-500 mt-1">Min 8 characters, with upper + lower case and a number.</p>
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                       class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-teal-700 focus:ring-brand-teal-700">
            </div>

            <x-turnstile />

            <button type="submit"
                    class="w-full bg-brand-orange hover:bg-brand-orange-dark text-white font-semibold py-3 rounded-full transition">
                Create Account
            </button>
        </form>

        <p class="text-sm text-center text-gray-600 mt-5">
            Already have an account? <a href="{{ route('login') }}" class="text-brand-teal font-semibold hover:underline">Login</a>
        </p>
    </div>
</div>

<script>
function countryPicker() {
    const countries = @json($countries->map(fn($c) => ['code' => $c->iso_code, 'name' => $c->name, 'phone' => $c->phone_code]));
    return {
        query: '',
        open: false,
        selected: @json(old('country_code', '')),
        init() {
            const found = countries.find(c => c.code === this.selected);
            if (found) this.query = found.name;
        },
        filtered() {
            const q = this.query.toLowerCase();
            if (!q) return countries.slice(0, 50);
            return countries.filter(c => c.name.toLowerCase().includes(q) || c.code.toLowerCase() === q).slice(0, 50);
        },
        choose(c) {
            this.selected = c.code;
            this.query = c.name;
            this.open = false;
        }
    };
}
</script>
@endsection
