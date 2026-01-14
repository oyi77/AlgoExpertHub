<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\ReferralController;

/*
|--------------------------------------------------------------------------
| Admin Referral Management Routes
|--------------------------------------------------------------------------
|
| Referral system management routes. Requires manage-referral permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-referral,admin'])->prefix('admin/refferal')->name('admin.refferal.')->group(function () {
    Route::get('/', [ReferralController::class, 'index'])->name('index');
    Route::post('/invest', [ReferralController::class, 'investStore'])->name('invest.store');
    Route::post('/status', [ReferralController::class, 'refferalStatusChange'])->name('status.change');
});
