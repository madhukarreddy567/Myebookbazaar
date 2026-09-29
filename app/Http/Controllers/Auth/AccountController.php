<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * My Account page. Country is shown read-only — customers cannot
     * change it after signup (only an admin can).
     */
    public function show(): View
    {
        return view('account.show', ['user' => auth()->user()]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated()['password']),
        ])->save();

        return back()->with('status', 'Your password has been changed.');
    }
}
