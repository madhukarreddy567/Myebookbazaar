<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        $countries = Country::orderBy('name')->get(['iso_code', 'name', 'phone_code']);

        return view('auth.register', compact('countries'));
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $country = Country::where('iso_code', $request->validated()['country_code'])->firstOrFail();

        $user = User::create([
            'name' => $request->validated()['name'],
            'email' => $request->validated()['email'],
            'password' => $request->validated()['password'], // hashed via model cast
            'country_code' => $country->iso_code,
            'country_name' => $country->name,
            // Pricing region is derived once at signup and never changes afterwards.
            'pricing_region' => $country->iso_code === 'IN' ? 'IN' : 'INTL',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        if (config('app.email_verification_enabled')) {
            event(new Registered($user));

            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('account'));
    }
}
