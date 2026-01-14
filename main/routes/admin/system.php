<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\ConfigurationController;
use App\Http\Controllers\Backend\PagesController;
use App\Http\Controllers\Backend\ManageSectionController;
use App\Http\Controllers\Backend\LanguageController;
use App\Http\Controllers\Backend\EmailTemplateController;

/*
|--------------------------------------------------------------------------
| Admin System Configuration Routes
|--------------------------------------------------------------------------
|
| System configuration routes including general settings, pages, sections,
| languages, email templates, and themes.
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    
    // General Settings
    Route::middleware('permission:manage-setting,admin')->prefix('general')->name('general.')->group(function () {
        Route::get('/index', [ConfigurationController::class, 'index'])->name('index');
        Route::post('/setting', [ConfigurationController::class, 'ConfigurationUpdate'])->name('basic');
        
        // Performance Settings
        Route::post('/performance/optimize', [ConfigurationController::class, 'performanceOptimize'])->name('performance.optimize');
        Route::post('/performance/clear', [ConfigurationController::class, 'performanceClear'])->name('performance.clear');
        Route::post('/performance/assets', [ConfigurationController::class, 'performanceAssets'])->name('performance.assets');
        Route::post('/performance/http', [ConfigurationController::class, 'performanceHttp'])->name('performance.http');
        Route::post('/performance/media', [ConfigurationController::class, 'performanceMedia'])->name('performance.media');
        Route::post('/performance/cache', [ConfigurationController::class, 'performanceCache'])->name('performance.cache');
        Route::post('/performance/database', [ConfigurationController::class, 'performanceDatabase'])->name('performance.database');
        Route::post('/performance/prewarm', [ConfigurationController::class, 'performancePrewarm'])->name('performance.prewarm');
        Route::get('/performance/tips', [ConfigurationController::class, 'getPerformanceTips'])->name('performance.tips');
        Route::get('/performance/n-plus-one', [ConfigurationController::class, 'analyzeNPlusOneQueries'])->name('performance.n-plus-one');
        Route::get('/performance/indexes', [ConfigurationController::class, 'analyzeDatabaseIndexes'])->name('performance.indexes');
        Route::get('/performance/cache-usage', [ConfigurationController::class, 'analyzeCacheUsage'])->name('performance.cache-usage');
        Route::get('/performance/pagination', [ConfigurationController::class, 'analyzePaginationUsage'])->name('performance.pagination');
        Route::get('/performance/chunking', [ConfigurationController::class, 'analyzeChunkingUsage'])->name('performance.chunking');
        Route::get('/performance/scan/models', [ConfigurationController::class, 'scanModels'])->name('performance.scan.models');
        Route::get('/performance/scan/controllers', [ConfigurationController::class, 'scanControllers'])->name('performance.scan.controllers');
        
        // Performance Monitoring Routes
        Route::get('/performance/status', [ConfigurationController::class, 'getSystemStatus'])->name('performance.status');
        Route::get('/performance/stream', [ConfigurationController::class, 'streamSystemStatus'])->name('performance.stream');
        
        // Database Operations
        Route::post('/reseed-database', [ConfigurationController::class, 'reseedDatabase'])->name('reseed-database');
        Route::post('/reset-database', [ConfigurationController::class, 'resetDatabase'])->name('reset-database');
    });
    
    // Cache Clear (accessible without specific permission check, only admin)
    Route::get('/cacheclear', [ConfigurationController::class, 'cacheClear'])->name('general.cacheclear');
    
    // Pages Management
    Route::middleware('permission:manage-frontend,admin')->group(function () {
        Route::get('/pages', [PagesController::class, 'index'])->name('frontend.pages');
        Route::get('/pages/create', [PagesController::class, 'pageCreate'])->name('frontend.pages.create');
        Route::post('/pages/create', [PagesController::class, 'pageInsert']);
        Route::get('/pages/edit/{id}', [PagesController::class, 'pageEdit'])->name('frontend.pages.edit');
        Route::post('/pages/edit/{id}', [PagesController::class, 'pageUpdate']);
        Route::get('/pages/search', [PagesController::class, 'index'])->name('frontend.search');
        Route::post('/pages/delete/{id}', [PagesController::class, 'pageDelete'])->name('frontend.pages.delete');
        // Route::get('/page-builder/{id?}', [PagesController::class, 'pageBuilder'])->name('page-builder.index'); // Disabled: Conflicts with page-builder-addon

        // Backward compatibility: Access pagebuilder from Manage Pages
        Route::get('/pages/{id}/page-builder', [PagesController::class, 'pageBuilder'])->name('pages.page-builder');
    
        Route::get('/manage/section/{name}', [ManageSectionController::class, 'section'])->name('frontend.section.manage');
        Route::post('/manage/section/{name}', [ManageSectionController::class, 'sectionContentUpdate']);
        Route::post('/manage/sections/reorder', [ManageSectionController::class, 'reorderSections'])->name('frontend.sections.reorder');
        Route::get('/manage/element/{name}', [ManageSectionController::class, 'sectionElement'])->name('frontend.element');
        Route::get('/manage/element/{name}/search', [ManageSectionController::class, 'section'])->name('frontend.element.search');
        Route::post('/manage/element/{name}', [ManageSectionController::class, 'sectionElementCreate']);
        Route::get('/edit/{name}/element/{element}', [ManageSectionController::class, 'editElement'])->name('frontend.element.edit');
        Route::post('/edit/{name}/element/{element}', [ManageSectionController::class, 'updateElement']);
        Route::post('/delete/{name}/element/{element}', [ManageSectionController::class, 'deleteElement'])->name('frontend.element.delete');

        Route::get('/frontend/translate/{name}/{element}', [ManageSectionController::class, 'translate'])->name('frontend.translate');
        Route::post('/frontend/translate/{name}/{element}', [ManageSectionController::class, 'translateUpdate']);

        // Backward compatibility: Access pagebuilder from Manage Section
        Route::get('/manage/section/{name}/page-builder', [ManageSectionController::class, 'pageBuilder'])->name('frontend.section.page-builder');
    });
    
    // Theme Management
    Route::middleware('permission:manage-theme,admin')->group(function () {
        Route::get('/manage-theme', [ConfigurationController::class, 'manageTheme'])->name('manage.theme');
        Route::post('/manage-theme/{name}', [ConfigurationController::class, 'themeUpdate'])->name('manage.theme.update');
        Route::post('/backend-theme/{name}', [ConfigurationController::class, 'backendThemeUpdate'])->name('manage.backend.theme.update');
        Route::post('/change/theme/color/{theme}', [ConfigurationController::class, 'themeColor'])->name('manage.theme.color');
        Route::post('/theme/upload', [ConfigurationController::class, 'themeUpload'])->name('manage.theme.upload');
        Route::get('/theme/download-template', [ConfigurationController::class, 'themeDownloadTemplate'])->name('manage.theme.download.template');
        Route::delete('/theme/delete/{theme}', [ConfigurationController::class, 'themeDelete'])->name('manage.theme.delete');
        Route::post('/theme/deactivate-all', [ConfigurationController::class, 'themeDeactivate'])->name('manage.theme.deactivate.all');
        Route::post('/landing/update', [ConfigurationController::class, 'landingPageUpdate'])->name('manage.landing.update');
        
        // Backward compatibility: Access pagebuilder from Manage Theme
        Route::get('/manage-theme/page-builder', [ConfigurationController::class, 'themePageBuilder'])->name('manage.theme.page-builder');
    });
    
    // Language Management
    Route::middleware('permission:manage-language,admin')->prefix('language')->name('language.')->group(function () {
        Route::get('/', [LanguageController::class, 'index'])->name('index');
        Route::post('/', [LanguageController::class, 'store'])->name('store');
        Route::put('/', [LanguageController::class, 'update'])->name('update');
        Route::delete('/', [LanguageController::class, 'delete'])->name('delete');
        Route::get('/translate', [LanguageController::class, 'transalate'])->name('translate');
        Route::post('/translate/update', [LanguageController::class, 'transalateUpate'])->name('translate.update');
        Route::post('/ajax/update', [LanguageController::class, 'ajaxUpdate'])->name('ajax.update');
        Route::get('/ajax', [LanguageController::class, 'languageAjax'])->name('ajax');
        Route::delete('/key', [LanguageController::class, 'deleteKey'])->name('key.delete');
        Route::post('/auto-translate', [LanguageController::class, 'autoTranslate'])->name('auto.translate');
    });
    
    // Language Change (accessible to all authenticated admins, no specific permission)
    Route::get('/changeLang', [LanguageController::class, 'changeLang'])->name('changeLang');
    
    // Email Configuration & Templates
    Route::middleware('permission:manage-email-template,admin')->prefix('email')->name('email.')->group(function () {
        Route::get('/config', [EmailTemplateController::class, 'emailConfig'])->name('config');
        Route::post('/config', [EmailTemplateController::class, 'emailConfigUpdate'])->name('config.update');
        Route::get('/templates', [EmailTemplateController::class, 'emailTemplates'])->name('templates');
        Route::get('/templates/{template}/edit', [EmailTemplateController::class, 'emailTemplatesEdit'])->name('templates.edit');
        Route::put('/templates/{template}', [EmailTemplateController::class, 'emailTemplatesUpdate'])->name('templates.update');
    });
});
