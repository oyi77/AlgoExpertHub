<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\PlanController;

/*
|--------------------------------------------------------------------------
| Admin Plan Management Routes
|--------------------------------------------------------------------------
|
| Subscription plan management routes. Requires manage-plan permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-plan,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('plan', PlanController::class);
    Route::post('plan/changestatus/{id}', [PlanController::class, 'planStatusChange'])->name('plan.changestatus');
});
