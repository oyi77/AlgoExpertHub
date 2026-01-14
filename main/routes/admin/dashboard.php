<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\DashboardController;

/*
|--------------------------------------------------------------------------
| Admin Dashboard Routes
|--------------------------------------------------------------------------
|
| Dashboard routes for admin panel. These routes require admin authentication.
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard
    Route::get('/', function () {
        return redirect()->route('admin.home');
    });
    Route::get('/home', [DashboardController::class, 'dashboard'])->name('home');
});
