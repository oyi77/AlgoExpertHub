<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentationController;

/*
|--------------------------------------------------------------------------
| Admin Documentation/Wiki Routes
|--------------------------------------------------------------------------
|
| Documentation and wiki routes for admin panel. Accessible to all authenticated admins.
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin/wiki')->name('admin.wiki.')->group(function () {
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    Route::get('/search', [DocumentationController::class, 'search'])->name('search');
    Route::get('/docs/{file}', [DocumentationController::class, 'showDocs'])->name('docs');
    Route::get('/{path?}', [DocumentationController::class, 'showWiki'])->name('show')->where('path', '.*');
});
