<?php

use Addons\PageBuilderAddon\App\Http\Controllers\Backend\GlobalStylesController;
use Addons\PageBuilderAddon\App\Http\Controllers\Backend\LayoutController;
use Addons\PageBuilderAddon\App\Http\Controllers\Backend\MenuController;
use Addons\PageBuilderAddon\App\Http\Controllers\Backend\PageBuilderController;
use Addons\PageBuilderAddon\App\Http\Controllers\Backend\SectionController;
use Addons\PageBuilderAddon\App\Http\Controllers\Backend\TemplateController;
use Addons\PageBuilderAddon\App\Http\Controllers\Backend\ThemeController;
use Addons\PageBuilderAddon\App\Http\Controllers\Backend\WidgetController;
use Illuminate\Support\Facades\Route;

// Pages
Route::get('/', [PageBuilderController::class, 'index'])->name('index');
Route::get('/create', [PageBuilderController::class, 'create'])->name('create');
Route::post('/', [PageBuilderController::class, 'store'])->name('store');
Route::get('/{id}/edit', [PageBuilderController::class, 'edit'])->name('edit');
Route::get('/pages/{pageId}/builder', [PageBuilderController::class, 'editFromPage'])->name('pages.builder');
Route::put('/{id}', [PageBuilderController::class, 'update'])->name('update');
Route::delete('/{id}', [PageBuilderController::class, 'destroy'])->name('destroy');

// Themes
Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
Route::get('/themes/create', [ThemeController::class, 'create'])->name('themes.create');
Route::post('/themes', [ThemeController::class, 'store'])->name('themes.store');
Route::get('/themes/edit', [ThemeController::class, 'edit'])->name('themes.edit');
Route::post('/themes/activate', [ThemeController::class, 'activate'])->name('themes.activate');
Route::post('/themes/upload', [ThemeController::class, 'upload'])->name('themes.upload');
Route::put('/themes/{themeName}', [ThemeController::class, 'updateTemplate'])->name('themes.update');

// Templates
Route::resource('templates', TemplateController::class);
Route::post('/templates/{id}/apply', [TemplateController::class, 'apply'])->name('templates.apply');

// Sections
Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
Route::get('/sections/{name}/edit', [SectionController::class, 'edit'])->name('sections.edit');
Route::put('/sections/{name}', [SectionController::class, 'update'])->name('sections.update');

// Menus
Route::resource('menus', MenuController::class);
Route::post('/menus/sync', [MenuController::class, 'sync'])->name('menus.sync');

// Layouts
Route::resource('layouts', LayoutController::class);

// Widgets
Route::resource('widgets', WidgetController::class);

// Global Styles
Route::resource('global-styles', GlobalStylesController::class);
Route::get('/global-styles/css/compiled', [GlobalStylesController::class, 'getCompiledCss'])->name('global-styles.css.compiled');

// API Routes for pagebuilder editor
Route::prefix('api')->name('api.')->group(function () {
    Route::post('/pages/{pageId}/content', [\Addons\PageBuilderAddon\App\Http\Controllers\Api\PageBuilderApiController::class, 'saveContent'])->name('pages.content.save');
    Route::get('/pages/{pageId}/content', [\Addons\PageBuilderAddon\App\Http\Controllers\Api\PageBuilderApiController::class, 'getContent'])->name('pages.content.get');
});
