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
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TicketController;
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
// URL always matches APP_URL). Throttled: sitemap generation is DB-heavy
// and a favourite scraper target; 30/min is plenty for real crawlers.
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])
    ->middleware('throttle:30,1')
    ->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::middleware('guest.browsing')->group(function () {
    // Public search / filter listings are DB-heavy (LIKE queries) — throttle
    // per IP so a scraper loop can't saturate the database. 60/min is
    // generous for human browsing.
    Route::get('/books', [BookController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('books');
    Route::get('/books/{slug}', [BookController::class, 'show'])->name('books.show');

    // Ebook preview: first N pages as protected images.
    Route::get('/books/{slug}/preview', [PreviewController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('books.preview');
    Route::get('/books/{slug}/preview/page/{page}', [PreviewController::class, 'page'])
        ->middleware('throttle:60,1')
        ->where('page', '[0-9]+')
        ->name('books.preview.page');

    // Phase 4: combos and offers.
    Route::get('/combos', [ComboController::class, 'index'])
        ->middleware(['feature:features.combos', 'throttle:60,1'])
        ->name('combos');
    Route::get('/combos/{slug}', [ComboController::class, 'show'])
        ->middleware('feature:features.combos')
        ->name('combos.show');
    Route::get('/offers', [OfferController::class, 'index'])
        ->middleware(['feature:features.sales_offers', 'throttle:60,1'])
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
        ->middleware(['feature:features.registration', 'throttle:5,1']);

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
        ->middleware(['feature:features.checkout', 'verified.if.enabled', 'throttle:20,1'])
        ->name('checkout.verify');
    Route::post('/checkout/report-failure', [CheckoutController::class, 'reportFailure'])
        ->middleware(['feature:features.checkout', 'verified.if.enabled', 'throttle:20,1'])
        ->name('checkout.report-failure');
    Route::get('/checkout/thank-you/{order}', [CheckoutController::class, 'thankyou'])
        ->middleware('verified.if.enabled')
        ->name('checkout.thankyou');
    Route::get('/checkout/payment-failed/{order}', [CheckoutController::class, 'paymentFailed'])
        ->middleware(['feature:features.checkout', 'verified.if.enabled'])
        ->name('checkout.payment-failed');

    Route::get('/orders', [OrderController::class, 'index'])
        ->middleware('verified.if.enabled')
        ->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('verified.if.enabled')
        ->name('orders.show');

    Route::get('/library', [LibraryController::class, 'index'])
        // Each view generates fresh signed download URLs (DB reads per
        // book), so the page gets the same 60/min per-IP throttle as
        // the other browsing listings — a scraper loop cannot turn it
        // into a cheap signed-URL mint.
        ->middleware(['verified.if.enabled', 'throttle:60,1'])
        ->name('library');

    // Signed download links (10-min expiry). Throttled per IP AFTER the
    // signature check so only valid, entitled downloads count — this caps
    // file-serving abuse (the most expensive operation on the box).
    Route::get('/download/{order}/{book}', [DownloadController::class, 'download'])
        ->middleware(['feature:features.downloads', 'signed', 'throttle:30,1'])
        ->name('download');

    /*
    |----------------------------------------------------------------------
    | Verified-purchase-only ratings. Eligibility is enforced server-side
    | by RatingPolicy (paid-order ownership). 5/min throttles review spam.
    |----------------------------------------------------------------------
    */
    Route::post('/books/{book:slug}/ratings', [RatingController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('books.ratings.store');
    Route::put('/books/{book:slug}/ratings/{rating}', [RatingController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('books.ratings.update');

    /*
    |----------------------------------------------------------------------
    | Support tickets (account area). Customers see ONLY their own
    | tickets — enforced by TicketPolicy on show/reply.
    |----------------------------------------------------------------------
    */
    Route::get('/account/support', [TicketController::class, 'index'])
        ->middleware('verified.if.enabled')
        ->name('support.index');
    Route::get('/account/support/new', [TicketController::class, 'create'])
        ->middleware('verified.if.enabled')
        ->name('support.create');
    Route::post('/account/support', [TicketController::class, 'store'])
        ->middleware(['verified.if.enabled', 'throttle:5,1'])
        ->name('support.store');
    Route::get('/account/support/{ticket}', [TicketController::class, 'show'])
        ->middleware('verified.if.enabled')
        ->name('support.show');
    Route::post('/account/support/{ticket}/reply', [TicketController::class, 'reply'])
        ->middleware(['verified.if.enabled', 'throttle:10,1'])
        ->name('support.reply');
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

/*
|----------------------------------------------------------------------
| Cookie consent — server-side persistence.
| The banner JS calls this endpoint to ensure the consent cookie is set
| with proper attributes (Secure on HTTPS, SameSite=Lax, 1-year expiry).
| This fixes the "popup keeps reappearing" issue where JS-set cookies
| weren't persisting reliably across sessions.
|----------------------------------------------------------------------
*/
Route::post('/cookie-consent', function (\Illuminate\Http\Request $request) {
    $choice = $request->input('choice') === 'accepted' ? 'accepted' : 'declined';

    return response()->json(['ok' => true])
        ->cookie(
            'meb_cookie_consent',
            $choice,
            525600, // 1 year in minutes
            '/',
            null,
            $request->isSecure(),
            false, // not httpOnly — JS needs to read it
            false,
            'Lax'
        );
})->name('cookie.consent');

