<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\ManageUserController;

/*
|--------------------------------------------------------------------------
| Admin User Management Routes
|--------------------------------------------------------------------------
|
| User management routes. Requires manage-user permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-user,admin'])->prefix('admin/user')->name('admin.user.')->group(function () {
    Route::get('/', [ManageUserController::class, 'index'])->name('index');
    Route::get('/details', [ManageUserController::class, 'userDetails'])->name('details');
    Route::put('/update', [ManageUserController::class, 'userUpdate'])->name('update');
    Route::post('/{user}/mail', [ManageUserController::class, 'sendUserMail'])->name('mail');
    Route::get('/disabled', [ManageUserController::class, 'disabled'])->name('disabled');
    Route::get('/filter/{status}', [ManageUserController::class, 'userStatusWiseFilter'])->name('filter');
    Route::get('/interest-log', [ManageUserController::class, 'interestLog'])->name('interest.log');
    Route::post('/balance/update', [ManageUserController::class, 'userBalanceUpdate'])->name('balance.update');
    Route::get('/login-as/{id}', [ManageUserController::class, 'loginAsUser'])->name('login-as');
    Route::get('/kyc-all', [ManageUserController::class, 'kycAll'])->name('kyc.req');
    Route::get('/kyc-details/{id}', [ManageUserController::class, 'kycDetails'])->name('kyc.details');
    Route::post('/kyc/{status}/{id}', [ManageUserController::class, 'kycStatus'])->name('kyc.status');
    Route::post('/bulk-mail', [ManageUserController::class, 'bulkMail'])->name('bulk.mail');
    Route::post('/{user}/change-password', [ManageUserController::class, 'changePassword'])->name('change-password');
});
