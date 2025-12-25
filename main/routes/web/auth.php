<?php

use App\Http\Controllers\Auth\FacebookController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\LoginSecurityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Guest and authenticated authentication routes including registration,
| login, password reset, 2FA, KYC, and social login.
|
*/

Route::name('user.')->group(function () {
    // Guest routes (registration, login, password reset, social login)
    Route::middleware('guest')->group(function () {
        Route::get('register/{reffer?}', [RegistrationController::class, 'index'])->name('register')->middleware('reg_off');
        Route::post('register/{reffer?}', [RegistrationController::class, 'register'])->name('register.post')->middleware('reg_off');

        Route::get('login', [LoginController::class, 'index'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.post');

        Route::get('auth/facebook', [FacebookController::class, 'redirectToFacebook'])->name('facebook.login');
        Route::get('auth/facebook/callback', [FacebookController::class, 'handleFacebookCallback']);

        Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
        Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

        Route::get('forgot/password', [ForgotPasswordController::class, 'index'])->name('forgot.password');
        Route::post('forgot/password', [ForgotPasswordController::class, 'sendVerification']);
        Route::get('verify/code', [ForgotPasswordController::class, 'verify'])->name('auth.verify');
        Route::post('verify/code', [ForgotPasswordController::class, 'verifyCode']);
        Route::get('reset/password', [ForgotPasswordController::class, 'reset'])->name('reset.password');
        Route::post('reset/password', [ForgotPasswordController::class, 'resetPassword']);

        Route::get('verify/email', [LoginController::class, 'emailVerify'])->name('email.verify');
        Route::post('verify/email', [LoginController::class, 'emailVerifyConfirm'])->name('email.verify.confirm');
    });

    // Authenticated auth routes (2FA, KYC, logout)
    Route::middleware(['auth', 'inactive', 'is_email_verified'])->group(function () {
        Route::get('2fa', [LoginSecurityController::class, 'show2faForm'])->name('2fa');
        Route::post('2fa/generateSecret', [LoginSecurityController::class, 'generate2faSecret'])->name('generate2faSecret');
        Route::post('2fa/enable2fa', [LoginSecurityController::class, 'enable2fa'])->name('enable2fa');
        Route::post('2fa/disable2fa', [LoginSecurityController::class, 'disable2fa'])->name('disable2fa');
        Route::post('2fa/2faVerify', function () {
            return redirect(URL()->previous());
        })->name('2faVerify')->middleware('2fa');

        Route::get('authentication-verify', [ForgotPasswordController::class, 'verifyAuth'])->name('authentication.verify')->withoutMiddleware('is_email_verified');
        Route::post('authentication-verify/email', [ForgotPasswordController::class, 'verifyEmailAuth'])->name('authentication.verify.email')->withoutMiddleware('is_email_verified');
        Route::post('authentication-verify/sms', [ForgotPasswordController::class, 'verifySmsAuth'])->name('authentication.verify.sms')->withoutMiddleware('is_email_verified');

        Route::get('logout', [LoginController::class, 'signOut'])->name('logout');

        Route::get('kyc', [KycController::class, 'kyc'])->name('kyc');
        Route::post('kyc', [KycController::class, 'kycUpdate']);
    });
});

