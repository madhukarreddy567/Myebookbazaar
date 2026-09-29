<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function show(): View
    {
        return view('admin.password');
    }

    public function update(AdminUpdatePasswordRequest $request): RedirectResponse
    {
        $request->user('admin')->forceFill([
            'password' => Hash::make($request->validated()['password']),
        ])->save();

        return back()->with('status', 'Admin password changed successfully.');
    }
}
