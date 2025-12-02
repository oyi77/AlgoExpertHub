<?php

use Addons\FilterStrategyAddon\App\Http\Controllers\Backend\FilterStrategyController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-addon,admin')->group(function () {
    Route::resource('filter-strategies', FilterStrategyController::class);
});

