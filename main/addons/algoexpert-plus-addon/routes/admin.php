<?php

use Addons\AlgoExpertPlus\App\Http\Controllers\Backend\AlgoExpertPlusController;
use Illuminate\Support\Facades\Route;

Route::prefix('algoexpert-plus')->name('algoexpert-plus.')->group(function () {
    // Main dashboard (Dependencies)
    Route::get('/', [AlgoExpertPlusController::class, 'index'])->name('index');
    
    // System Tools submenu
    Route::prefix('system-tools')->name('system-tools.')->group(function () {
        Route::get('/dashboard', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\SystemToolsController::class, 'dashboard'])->name('dashboard');
        Route::get('/performance', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\PerformanceController::class, 'index'])->name('performance');
        Route::get('/cron-jobs', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\CronJobController::class, 'index'])->name('cron-jobs');
        Route::get('/cron-jobs/generate', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\CronJobController::class, 'generateCrontab'])->name('cron-jobs.generate');
        Route::post('/cron-jobs/test', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\CronJobController::class, 'testCron'])->name('cron-jobs.test');
    });
    
    // Backup routes
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\BackupController::class, 'index'])->name('index');
        Route::post('/run', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\BackupController::class, 'run'])->name('run');
        Route::post('/clean', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\BackupController::class, 'clean'])->name('clean');
        Route::get('/download', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\BackupController::class, 'download'])->name('download');
        Route::delete('/delete', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\BackupController::class, 'delete'])->name('delete');
    });
    
    // Other routes
    Route::get('/system-health', [AlgoExpertPlusController::class, 'systemHealth'])->name('system-health');
    Route::post('/install-dependencies', [AlgoExpertPlusController::class, 'installDependencies'])->name('install-dependencies');
    
    // Embedded Horizon dashboard
    Route::get('/horizon', [\Addons\AlgoExpertPlus\App\Http\Controllers\Backend\HorizonController::class, 'index'])->name('horizon');
});
