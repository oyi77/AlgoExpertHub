<?php

use App\Http\Controllers\CryptoTradeController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Public-facing routes including home, blog, docs, PWA, and catch-all.
|
*/

// Home route
Route::get('/', [FrontendController::class, 'index'])->name('home');

// Documentation routes
Route::get('/swagger', function () {
    return view('swagger');
})->name('swagger');

Route::get('/docs', function () {
    return view('swagger');
})->name('docs');

Route::get('/docs.openapi', function () {
    $path = storage_path('app/scribe/openapi.yaml');
    if (!file_exists($path)) {
        abort(404);
    }
    return response(file_get_contents($path))
        ->header('Content-Type', 'application/yaml');
})->name('scribe.openapi');

Route::get('/docs.postman', function () {
    $path = storage_path('app/scribe/collection.json');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Content-Type' => 'application/json'
    ]);
})->name('scribe.postman');

Route::get('/styleguide', function () {
    return view('styleguide');
})->name('styleguide');

// Trading return route
Route::get('trading-return', [CryptoTradeController::class, 'tradingInterest'])->name('trading-interest');

// Language change route
Route::get('change-language', [FrontendController::class, 'changeLanguage'])->name('change-language');

// API routes - must be before catch-all route
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('current-price', [CryptoTradeController::class, 'currentPrice'])->name('user.current-price');
    Route::get('get-ticker', [CryptoTradeController::class, 'latestTicker'])->name('ticker');
    Route::get('stream-prices', [CryptoTradeController::class, 'streamPrices'])->name('stream.prices');
});

// Blog and links routes
Route::get('blog/{id}/{slug}', [FrontendController::class, 'blogDetails'])->name('blog.details');
Route::get('links/{id}/{slug}', [FrontendController::class, 'linksDetails'])->name('links');

// Subscription and contact routes
Route::post('subscribe', [FrontendController::class, 'subscribe'])->name('subscribe');
Route::post('contact', [FrontendController::class, 'contactSend'])->name('contact');

// Landing pages
Route::get('landing/algo-expert-premium', function () {
    return view('frontend.landings.algo-expert-premium.index');
})->name('landing.algo-expert-premium');

// PWA Routes
Route::get('manifest.json', [\App\Http\Controllers\PWAController::class, 'manifest'])->name('pwa.manifest');
Route::get('sw.js', [\App\Http\Controllers\PWAController::class, 'serviceWorker'])->name('pwa.serviceworker');
Route::get('offline', [\App\Http\Controllers\PWAController::class, 'offline'])->name('pwa.offline');
Route::get('install', [\App\Http\Controllers\PWAController::class, 'install'])->name('pwa.install');

// Catch-all route for dynamic CMS pages
// This must be LAST to avoid interfering with other routes
Route::get('{pages}', [FrontendController::class, 'page'])->name('pages')->where('pages', '^(?!admin|api).*$');

