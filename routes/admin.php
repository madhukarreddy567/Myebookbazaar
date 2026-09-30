<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ComboController as AdminComboController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\RatingController as AdminRatingController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SecurityLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\Admin\UserController as AdminUserMgmtController;
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

        // Phase 6: TOTP 2FA challenge after a correct password (guest
        // context; the pending admin id lives in the session).
        Route::get('/2fa/challenge', [TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
        Route::post('/2fa/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('2fa.challenge.verify');
    });

    Route::middleware(['admin', 'require_2fa'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dismiss-2fa-warning', [DashboardController::class, 'dismiss2faWarning'])
            ->name('dismiss-2fa-warning');

        Route::get('/password', [PasswordController::class, 'show'])->name('password');
        Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

        // Settings Center (Phase 2A) — Super Admin only.
        Route::middleware('super_admin')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
            Route::post('/settings/test-razorpay', [SettingsController::class, 'testRazorpay'])->name('settings.test-razorpay');
        });

        // Store management (Phase 2B) — every change is audited.
        Route::resource('books', AdminBookController::class)->except(['show']);
        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('banners', AdminBannerController::class)->except(['show']);
        Route::resource('pages', AdminPageController::class)->except(['show']);

        // Coupons, combos, offers (Phase 4) — every change is audited.
        Route::post('/coupons/{coupon}/duplicate', [AdminCouponController::class, 'duplicate'])->name('coupons.duplicate');
        Route::get('/coupons/{coupon}/usages/export', [AdminCouponController::class, 'exportUsages'])->name('coupons.usages.export');
        Route::resource('coupons', AdminCouponController::class);
        Route::resource('combos', AdminComboController::class)->except(['show']);
        Route::get('/offers', [AdminOfferController::class, 'index'])->name('offers.index');

        // Orders (Phase 3). Phase 5: CSV export.
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('orders.export');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/mark-paid', [AdminOrderController::class, 'markPaid'])->name('orders.mark-paid');

        // Users (Phase 5). List/show for all admins; block/unblock is
        // Super Admin only (enforced in the controller).
        Route::get('/users/export', [AdminUserMgmtController::class, 'export'])->name('users.export');
        Route::get('/users', [AdminUserMgmtController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserMgmtController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/block', [AdminUserMgmtController::class, 'block'])->name('users.block');
        Route::post('/users/{user}/unblock', [AdminUserMgmtController::class, 'unblock'])->name('users.unblock');
        Route::put('/users/{user}/country', [AdminUserMgmtController::class, 'updateCountry'])->name('users.country');

        // Reviews — verified-purchase rating moderation (hide/unhide/delete,
        // every action audit-logged).
        Route::get('/reviews', [AdminRatingController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{rating}/toggle', [AdminRatingController::class, 'toggle'])->name('reviews.toggle');
        Route::delete('/reviews/{rating}', [AdminRatingController::class, 'destroy'])->name('reviews.destroy');

        // Support tickets — inbox, thread, reply, close/reopen.
        // Every action is audit-logged.
        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/close', [AdminTicketController::class, 'close'])->name('tickets.close');
        Route::post('/tickets/{ticket}/reopen', [AdminTicketController::class, 'reopen'])->name('tickets.reopen');

        // Sessions (Phase 5) — the logged-in admin's own sessions.
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::delete('/sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');

        // Phase 6: account security + TOTP 2FA for the logged-in admin.
        Route::get('/account/security', [TwoFactorController::class, 'showSecurity'])->name('account.security');
        Route::get('/account/security/2fa', [TwoFactorController::class, 'showSetup'])->name('2fa.setup');
        Route::post('/account/security/2fa', [TwoFactorController::class, 'confirmSetup'])->name('2fa.confirm');
        Route::delete('/account/security/2fa', [TwoFactorController::class, 'disable'])->name('2fa.disable');

        // Super Admin only: admin accounts, audit log, security log.
        Route::middleware('super_admin')->group(function () {
            Route::get('/admins', [AdminUserController::class, 'index'])->name('admins.index');
            Route::post('/admins', [AdminUserController::class, 'store'])->name('admins.store');
            Route::patch('/admins/{admin}/toggle', [AdminUserController::class, 'toggle'])->name('admins.toggle');
            Route::delete('/admins/{admin}', [AdminUserController::class, 'destroy'])->name('admins.destroy');

            Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

            // Phase 6: security events (Super Admin only).
            Route::get('/security-log', [SecurityLogController::class, 'index'])->name('security-log.index');

            // Phase 6: Turnstile test connection.
            Route::post('/settings/test-turnstile', [SettingsController::class, 'testTurnstile'])->name('settings.test-turnstile');

            // Phase 7: GitHub auto-updater + backups (Super Admin only).
            Route::post('/settings/test-github', [UpdateController::class, 'testGithub'])->name('settings.test-github');
            // Google OAuth sign-in test connection (Super Admin only).
            Route::post('/settings/test-google', [SettingsController::class, 'testGoogle'])->name('settings.test-google');
            Route::get('/updates', [UpdateController::class, 'index'])->name('updates.index');
            Route::post('/updates/check', [UpdateController::class, 'check'])->name('updates.check');
            Route::get('/updates/confirm', [UpdateController::class, 'confirm'])->name('updates.confirm');
            Route::post('/updates/apply', [UpdateController::class, 'apply'])->name('updates.apply');
            Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
            Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
            Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
            Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        });

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
