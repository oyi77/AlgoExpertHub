<?php

use Illuminate\Support\Facades\Route;

Route::prefix('algoexpert-plus')->name('algoexpert-plus.')->group(function () {
    Route::get('/', function () {
        $data = [
            'title' => 'AlgoExpert++'
        ];
        return view('backend.index')->with($data);
    })->name('index');

    // Run system backup (gated by addon module in provider registration)
    Route::get('/backup/run', function () {
        try {
            \Artisan::call('backup:run');
            return redirect()->route('admin.algoexpert-plus.index')
                ->with('success', 'Backup started');
        } catch (\Throwable $e) {
            return redirect()->route('admin.algoexpert-plus.index')
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    })->name('backup.run');

    // Link to health dashboard if available
    Route::get('/health', function () {
        if (\Illuminate\Support\Facades\Route::has('health')) {
            return redirect()->route('health');
        }
        return redirect()->route('admin.algoexpert-plus.index')
            ->with('error', 'Health dashboard not available');
    })->name('health');
});
