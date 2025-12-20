<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onboarding Routes
|--------------------------------------------------------------------------
|
| User onboarding routes (must be before check_onboarding middleware).
|
*/

Route::name('user.')->middleware(['auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])->group(function () {
    // Onboarding routes (must be before check_onboarding to allow onboarding access)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/welcome', [\App\Http\Controllers\User\OnboardingController::class, 'welcome'])->name('welcome');
        Route::post('/welcome', [\App\Http\Controllers\User\OnboardingController::class, 'completeWelcome'])->name('welcome.complete');
        Route::get('/step/{step}', [\App\Http\Controllers\User\OnboardingController::class, 'step'])->name('step');
        Route::post('/step/{step}', [\App\Http\Controllers\User\OnboardingController::class, 'completeStep'])->name('step.complete');
        Route::post('/skip', [\App\Http\Controllers\User\OnboardingController::class, 'skip'])->name('skip');
        Route::get('/complete', [\App\Http\Controllers\User\OnboardingController::class, 'complete'])->name('complete');
    });
});

