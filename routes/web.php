<?php

use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Admin routes live in routes/admin.php (prefix /reddy — never /admin).
require __DIR__.'/admin.php';

/*
|--------------------------------------------------------------------------
| Public pages — no login required
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/books', function () {
    return view('pages.books');
})->name('books');

Route::view('/about', 'pages.about')->name('about');
Route::view('/privacy-policy', 'pages.privacy')->name('privacy');
Route::view('/no-refund-policy', 'pages.no-refund')->name('no-refund');
Route::view('/terms', 'pages.terms')->name('terms');

/*
|--------------------------------------------------------------------------
| Customer authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest:web')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    // 5 attempts per minute (see LoginRequest for the per-email+IP key).
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:3,1')
        ->name('password.update');
});

Route::middleware('customer')->group(function () {
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.send');

    // "auth required" reusable middleware for future Buy/Checkout actions.
    Route::get('/account', [AccountController::class, 'show'])
        ->middleware('verified.if.enabled')
        ->name('account');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('verified.if.enabled')
        ->name('account.password');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
