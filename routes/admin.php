<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes — prefixed /reddy. There is intentionally NO /admin route
| anywhere in this application (it must return 404).
|--------------------------------------------------------------------------
*/
Route::prefix('reddy')->name('reddy.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        // 5 attempts/minute per IP+email enforced in the controller.
        Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    });

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/password', [PasswordController::class, 'show'])->name('password');
        Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
