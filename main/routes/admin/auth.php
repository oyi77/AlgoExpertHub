<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\Auth\LoginController;
use App\Http\Controllers\Backend\Auth\ForgotPasswordController;
use App\Http\Controllers\Backend\Auth\ResetPasswordController;

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
|
| Public authentication routes for admin panel (login, password reset).
| These routes are accessible without admin authentication.
|
*/

// Public admin authentication routes (no admin middleware)
Route::middleware(['web'])->prefix('admin')->name('admin.')->group(function () {
    // Login routes
    Route::get('/login', [LoginController::class, 'loginPage'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    
    // Password reset routes
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.reset');
    Route::post('/password/reset', [ForgotPasswordController::class, 'sendResetCodeEmail'])->name('password.reset.post');
    Route::get('/password/verify-code', [ForgotPasswordController::class, 'verifyCodeForm'])->name('password.verify.code');
    Route::post('/password/verify-code', [ForgotPasswordController::class, 'verifyCode'])->name('password.verify.code.post');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset/{token}', [ResetPasswordController::class, 'reset'])->name('password.reset.token');
    Route::post('/password/resend', [ResetPasswordController::class, 'sendAgain'])->name('password.resend');
});

// Protected admin routes (require admin authentication)
Route::middleware(['web', 'admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    // Admin logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
