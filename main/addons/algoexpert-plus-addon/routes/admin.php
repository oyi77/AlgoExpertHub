<?php

use Illuminate\Support\Facades\Route;

Route::prefix('algoexpert-plus')->name('algoexpert-plus.')->group(function () {
    Route::get('/', function () {
        $data = [
            'title' => 'AlgoExpert++'
        ];
        return view('backend.index')->with($data);
    })->name('index');
});

