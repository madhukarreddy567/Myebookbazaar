<?php

use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Admin routes live in routes/admin.php (prefix /reddy — never /admin).
require __DIR__.'/admin.php';

/*
|--------------------------------------------------------------------------
| Public store (Phase 2B)
|--------------------------------------------------------------------------
*/
Route::get('/', [StoreController::class, 'index'])->name('home');

// SEO: dynamic sitemap and robots.txt (served by the app so the sitemap
// URL always matches APP_URL).
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::middleware('guest.browsing')->group(function () {
    Route::get('/books', [BookController::class, 'index'])->name('books');
    Route::get('/books/{slug}', [BookController::class, 'show'])->name('books.show');

    // Phase 4: combos and offers.
    Route::get('/combos', [ComboController::class, 'index'])
        ->middleware('feature:features.combos')
        ->name('combos');
    Route::get('/combos/{slug}', [ComboController::class, 'show'])
        ->middleware('feature:features.combos')
        ->name('combos.show');
    Route::get('/offers', [OfferController::class, 'index'])
        ->middleware('feature:features.sales_offers')
        ->name('offers');
});
Route::post('/books/{slug}/buy-now', [BookController::class, 'buyNow'])
    ->middleware('feature:features.buy_now')
    ->name('books.buy-now');
Route::post('/combos/{slug}/buy', [ComboController::class, 'buy'])
    ->middleware('feature:features.combos')
    ->name('combos.buy');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::delete('/cart/{cart}', [CartController::class, 'destroy'])->name('cart.destroy');

/*
|--------------------------------------------------------------------------
| Public pages — no login required
|--------------------------------------------------------------------------
*/
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/no-refund-policy', [PageController::class, 'noRefund'])->name('no-refund');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::get('/contact', [PageController::class, 'contact'])
    ->middleware('feature:features.contact_form')
    ->name('contact');
Route::post('/contact', [PageController::class, 'contactSend'])
    ->middleware(['feature:features.contact_form', 'throttle:3,1'])
    ->name('contact.send');

/*
|--------------------------------------------------------------------------
| Customer authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest:web')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])
        ->middleware('feature:features.registration')
        ->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware(['feature:features.registration']);

    Route::get('/login', [LoginController::class, 'show'])
        ->middleware('feature:features.login')
        ->name('login');
    // 5 attempts per minute (see LoginRequest for the per-email+IP key).
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware(['feature:features.login', 'throttle:5,1']);

    // Google OAuth sign-in (customer). The buttons render only when the
    // feature is switched on and credentials are saved in Settings.
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:10,1')
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('auth.google.callback');

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
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])
        ->middleware('verified.if.enabled')
        ->name('account.profile');

    // Phase 6: privacy controls — data export and account deletion.
    Route::get('/account/export', [PrivacyController::class, 'export'])
        ->middleware('verified.if.enabled')
        ->name('account.export');
    Route::get('/account/delete', [PrivacyController::class, 'confirmDelete'])
        ->middleware('verified.if.enabled')
        ->name('account.delete');
    Route::delete('/account', [PrivacyController::class, 'destroy'])
        ->middleware('verified.if.enabled')
        ->name('account.destroy');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Phase 3: checkout, orders, library, downloads
    |----------------------------------------------------------------------
    */
    Route::get('/checkout', [CheckoutController::class, 'show'])
        ->middleware(['feature:features.checkout', 'verified.if.enabled'])
        ->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware(['feature:features.checkout', 'verified.if.enabled', 'throttle:10,1']);

    // Phase 4: coupon apply/remove (server-side only).
    Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon'])
        ->middleware(['feature:features.checkout', 'feature:features.coupons', 'verified.if.enabled', 'throttle:20,1'])
        ->name('checkout.coupon.apply');
    Route::delete('/checkout/coupon', [CheckoutController::class, 'removeCoupon'])
        ->middleware(['feature:features.checkout', 'feature:features.coupons', 'verified.if.enabled'])
        ->name('checkout.coupon.remove');
    Route::post('/checkout/verify', [CheckoutController::class, 'verify'])
        ->middleware(['feature:features.checkout', 'throttle:20,1'])
        ->name('checkout.verify');
    Route::post('/checkout/report-failure', [CheckoutController::class, 'reportFailure'])
        ->middleware(['feature:features.checkout', 'throttle:20,1'])
        ->name('checkout.report-failure');
    Route::get('/checkout/thank-you/{order}', [CheckoutController::class, 'thankyou'])
        ->middleware('verified.if.enabled')
        ->name('checkout.thankyou');

    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('verified.if.enabled')
        ->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('verified.if.enabled')
        ->name('orders.show');

    Route::get('/library', [LibraryController::class, 'index'])
        ->middleware('verified.if.enabled')
        ->name('library');

    Route::get('/download/{order}/{book}', [DownloadController::class, 'download'])
        ->middleware(['feature:features.downloads', 'signed'])
        ->name('download');
});

/*
|----------------------------------------------------------------------
| Phase 3: Razorpay webhook — no session, no CSRF. Authenticity comes
| from the HMAC signature (see bootstrap/app.php for the CSRF exception).
|----------------------------------------------------------------------
*/
Route::post('/webhooks/razorpay', [WebhookController::class, 'razorpay'])
    ->middleware('throttle:60,1')
    ->name('webhooks.razorpay');

