<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\RoleController;

/*
|--------------------------------------------------------------------------
| Admin Role & Permission Management Routes
|--------------------------------------------------------------------------
|
| Role and permission management routes. Requires manage-role permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-role,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('roles', RoleController::class, ['except' => ['show', 'delete', 'edit']]);
});
