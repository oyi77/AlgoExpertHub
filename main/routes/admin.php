<?php

use Illuminate\Support\Facades\Route;
use App\Support\AddonRegistry;

use App\Http\Controllers\Backend\Auth\{
    ForgotPasswordController,
    ResetPasswordController,
    LoginController,
};
use App\Http\Controllers\Backend\{
    AddonController,
    AdminController,
    AdminProfileController,
    DashboardController,
    EmailTemplateController,
    ConfigurationController,
    LanguageController,
    LogController,
    ManageDepositController,
    ManageGatewayController,
    ManageSectionController,
    ManageUserController,
    ManageWithdrawController,
    MarketController,
    PagesController,
    PaymentController,
    PlanController,
    ReferralController,
    RoleController,
    SignalController,
    SignalCurrencyPairController,
    SignalTimeFrameController,
    TicketController
};



Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('login', [LoginController::class, 'loginPage'])->name('login');

    Route::post('login', [LoginController::class, 'login']);


    Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.reset');

    Route::post('password/reset', [ForgotPasswordController::class, 'sendResetCodeEmail']);

    Route::get('password/verify-code', [ForgotPasswordController::class, 'verifyCodeForm'])->name('password.verify.code');

    Route::post('password/verify-code', [ForgotPasswordController::class, 'verifyCode']);

    Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset.form');

    Route::get('password/send/again', [ResetPasswordController::class,  'sendAgain'])->name('send.again');

    Route::post('password/reset/change', [ResetPasswordController::class, 'reset'])->name('password.change');


    Route::middleware(['admin', 'demo'])->group(function () {

        Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('home');

        Route::get('profile', [AdminProfileController::class, 'profile'])->name('profile');

        Route::post('profile', [AdminProfileController::class, 'profileUpdate']);

        Route::post('change/password', [AdminProfileController::class, 'changePassword'])->name('change.password');

        Route::get('logout', [LoginController::class, 'logout'])->name('logout');


        Route::get('language/ajax',[LanguageController::class ,'languageAjax'])->name('cms-builder');


        Route::middleware('permission:manage-addon,admin')->prefix('addons')->name('addons.')->group(function () {
            Route::get('/', [AddonController::class, 'index'])->name('index');
            Route::post('upload', [AddonController::class, 'upload'])->name('upload');
            Route::post('{addon}/status', [AddonController::class, 'updateStatus'])->name('status');
            Route::get('{addon}/modules', [AddonController::class, 'modules'])->name('modules');
            Route::post('{addon}/modules/{module}', [AddonController::class, 'updateModule'])->name('modules.update');
        });

        // Plan
        Route::middleware('permission:manage-plan,admin')->group(function () {
            Route::resource('plan', PlanController::class);
            Route::post('plan/changestatus/{id}', [PlanController::class, 'planStatusChange'])->name('plan.changestatus');
        });
        // Signal Tools

        Route::middleware('permission:signal,admin')->group(function () {
            Route::resource('currency-pair', SignalCurrencyPairController::class);
            Route::post('currency-pair/changeStatus/{id}', [SignalCurrencyPairController::class, 'changeStatus'])->name('currency-pair.changestatus');


            Route::resource('markets', MarketController::class);
            Route::post('markets/changeStatus/{id}', [MarketController::class, 'changeStatus'])->name('markets.changestatus');


            Route::resource('frames', SignalTimeFrameController::class);
            Route::post('frames/changeStatus/{id}', [SignalTimeFrameController::class, 'changeStatus'])->name('frames.changestatus');


            Route::resource('signals', SignalController::class);
            Route::post('signals/send/{id}', [SignalController::class, 'sent'])->name('signals.sent');

            // Channel Signals (Auto-Created) - Only register if addon is active
            if (AddonRegistry::active('multi-channel-signal-addon') && AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'admin_ui')) {
            Route::prefix('channel-signals')->name('channel-signals.')->group(function () {
                Route::get('/', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'index'])->name('index');
                Route::get('/{id}', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'edit'])->name('edit');
                Route::post('/{id}', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'update'])->name('update');
                Route::post('/{id}/approve', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'approve'])->name('approve');
                Route::post('/{id}/reject', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'reject'])->name('reject');
                Route::post('/bulk/approve', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'bulkApprove'])->name('bulk.approve');
                Route::post('/bulk/reject', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'bulkReject'])->name('bulk.reject');
            });
            }
        });

        // Role Permission

        Route::resource('roles', RoleController::class, ['except' => ['show', 'delete', 'edit']])->middleware('permission:manage-role,admin');

        Route::resource('admins', AdminController::class)->middleware('permission:manage-admin,admin');
        Route::post('admins/changeStatus/{id}', [AdminController::class, 'changeStatus'])->name('changestatus')->middleware('permission:manage-admin,admin');

        // Manage User
        Route::middleware('permission:manage-user,admin')->prefix('users')->name('user.')->group(function () {

            Route::get('/', [ManageUserController::class, 'index'])->name('index');
            Route::get('details/{user}', [ManageUserController::class, 'userDetails'])->name('details');
            Route::post('update/{user}', [ManageUserController::class, 'userUpdate'])->name('update');
            Route::post('balance/{user}', [ManageUserController::class, 'userBalanceUpdate'])->name('balance.update');
            Route::post('mail/{user}', [ManageUserController::class, 'sendUserMail'])->name('mail');
            Route::get('{status}', [ManageUserController::class, 'userStatusWiseFilter'])->name('filter');
            Route::get('interest/log', [ManageUserController::class, 'interestLog'])->name('interestlog');

            Route::post('bulk/mail', [ManageUserController::class, 'bulkMail'])->name('bulk');

            Route::get('kyc/request', [ManageUserController::class, 'kycAll'])->name('kyc.req');
            Route::get('kyc/request/{id}', [ManageUserController::class, 'kycDetails'])->name('kyc.details');
            Route::post('kyc/{status}/{id}', [ManageUserController::class, 'kycStatus'])->name('kyc.status');

            Route::get('login/user/{id}', [ManageUserController::class, 'loginAsUser'])->name('login');
        });
        // End User


        // General Settings
        Route::middleware('permission:manage-setting,admin')->prefix('general')->name('general.')->group(function () {

            Route::get('index', [ConfigurationController::class, 'index'])->name('index');

            Route::post('setting', [ConfigurationController::class, 'ConfigurationUpdate'])->name('basic');

            // Performance Settings
            Route::post('performance/optimize', [ConfigurationController::class, 'performanceOptimize'])->name('performance.optimize');
            Route::post('performance/clear', [ConfigurationController::class, 'performanceClear'])->name('performance.clear');
            Route::get('performance/status', [ConfigurationController::class, 'getSystemStatus'])->name('performance.status');
            Route::get('performance/stream', [ConfigurationController::class, 'streamSystemStatus'])->name('performance.stream');

            // Performance: granular actions & toggles
            Route::post('performance/assets', [ConfigurationController::class, 'performanceAssets'])->name('performance.assets');
            Route::post('performance/http', [ConfigurationController::class, 'performanceHttp'])->name('performance.http');
            Route::post('performance/media', [ConfigurationController::class, 'performanceMedia'])->name('performance.media');
            Route::post('performance/cache', [ConfigurationController::class, 'performanceCache'])->name('performance.cache');
            Route::post('performance/db', [ConfigurationController::class, 'performanceDatabase'])->name('performance.db');
            Route::post('performance/prewarm', [ConfigurationController::class, 'performancePrewarm'])->name('performance.prewarm');

            // Database Management
            Route::post('reseed-database', [ConfigurationController::class, 'reseedDatabase'])->name('reseed-database');
            Route::post('reset-database', [ConfigurationController::class, 'resetDatabase'])->name('reset-database');
            
            // Database Backup/Restore
            Route::post('backup-create', [ConfigurationController::class, 'createBackup'])->name('backup-create');
            Route::post('backup-load', [ConfigurationController::class, 'loadBackup'])->name('backup-load');
            Route::post('backup-delete', [ConfigurationController::class, 'deleteBackup'])->name('backup-delete');
            Route::post('backup-save-factory', [ConfigurationController::class, 'saveAsFactoryState'])->name('backup-save-factory');
            Route::post('backup-load-factory', [ConfigurationController::class, 'loadFactoryState'])->name('backup-load-factory');
        });

        // End General Settings


        // support Ticket

        Route::middleware('permission:manage-ticket,admin')->prefix('ticket')->name('ticket.')->group(function () {
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::get('/{id}', [TicketController::class, 'show'])->name('show');
            Route::post('ticket/reply/{id}', [TicketController::class, 'reply'])->name('reply');
            Route::get('filter/{status}', [TicketController::class, 'filterByStatus'])->name('status');
            Route::delete('destroy/{id}', [TicketController::class, 'destroy'])->name('destroy');
        });



        // Email Manager
        Route::middleware('permission:manage-email,admin')->prefix('email')->name('email.')->group(function () {

            Route::get('config', [EmailTemplateController::class, 'emailConfig'])->name('config');
            Route::post('config', [EmailTemplateController::class, 'emailConfigUpdate']);
            Route::get('templates', [EmailTemplateController::class, 'emailTemplates'])->name('templates');
            Route::get('templates/{template}', [EmailTemplateController::class, 'emailTemplatesEdit'])->name('templates.edit');
            Route::post('templates/{template}', [EmailTemplateController::class, 'emailTemplatesUpdate']);
        });


        Route::middleware('permission:manage-referral,admin')->prefix('refferal')->name('refferal.')->group(function () {
            Route::get('/', [ReferralController::class, 'index'])->name('index');
            Route::post('invest', [ReferralController::class, 'investStore'])->name('invest');
            Route::post('status/{id?}', [ReferralController::class, 'refferalStatusChange'])->name('refferalstatus');
        });


        Route::middleware('permission:manage-gateway,admin')->prefix('gateway')->name('payment.')->group(function () {
            Route::get('online', [ManageGatewayController::class, 'online'])->name('index');
            Route::get('offline', [ManageGatewayController::class, 'offline'])->name('offline');
            Route::get('/{name}', [ManageGatewayController::class, 'loadView'])->name('gateway');
            Route::post('status/{id}', [ManageGatewayController::class, 'status'])->name('status');
            Route::post('update/online/{id}', [ManageGatewayController::class, 'updateOnlinePaymentGateway'])->name('update.online');
            Route::post('gourl', [ManageGatewayController::class, 'gourlUpdate'])->name('update.gourl');
            Route::get('offline-gateway/create', [ManageGatewayController::class, 'offlineCreate'])->name('offline.create');
            Route::post('offline-gateway/create', [ManageGatewayController::class, 'offlineStore']);
            Route::get('offline-gateway/edit/{id}', [ManageGatewayController::class, 'offlineEdit'])->name('offline.edit');
            Route::post('offline-gateway/edit/{id}', [ManageGatewayController::class, 'offlineUpdate']);
        });


        Route::middleware('permission:manage-language,admin')->prefix('language')->name('language.')->group(function () {
            Route::get('/', [LanguageController::class, 'index'])->name('index');
            Route::post('/', [LanguageController::class, 'store']);
            Route::post('edit/{id}', [LanguageController::class, 'update'])->name('edit');
            Route::post('delete/{id}', [LanguageController::class, 'delete'])->name('delete');


            Route::get('translator/{lang}', [LanguageController::class, 'transalate'])->name('translator');
            Route::post('translator/{lang}', [LanguageController::class, 'transalateUpate']);

            Route::post('translator/ajax/update/{lang}', [LanguageController::class, 'ajaxUpdate'])->name('ajax');
            Route::post('translator/delete/{lang}', [LanguageController::class, 'deleteKey'])->name('key.delete');
            Route::post('translator/auto-translate/{lang}', [LanguageController::class, 'autoTranslate'])->name('auto.translate');

            // Translation AI Settings
            Route::get('translation-settings', [\App\Http\Controllers\Backend\TranslationSettingController::class, 'index'])->name('translation-settings.index');
            Route::post('translation-settings', [\App\Http\Controllers\Backend\TranslationSettingController::class, 'update'])->name('translation-settings.update');
            Route::post('translation-settings/test', [\App\Http\Controllers\Backend\TranslationSettingController::class, 'test'])->name('translation-settings.test');

        });


        Route::middleware('permission:manage-withdraw,admin')->prefix('withdraw')->name('withdraw.')->group(function () {

            Route::get('method', [ManageWithdrawController::class, 'index'])->name('index');
            Route::get('method/search', [ManageWithdrawController::class, 'index'])->name('search');
            Route::post('method', [ManageWithdrawController::class, 'withdrawMethodCreate']);
            Route::post('edit/{id}', [ManageWithdrawController::class, 'withdrawMethodUpdate'])->name('update');
            Route::post('delete/{id}', [ManageWithdrawController::class, 'withdrawMethodDelete'])->name('delete');

            Route::get('withdraw-log/{id}', [ManageWithdrawController::class, 'withdrawLog'])->name('log');
            Route::get('{status?}', [ManageWithdrawController::class, 'filterWithdraw'])->name('filter');
            Route::post('accept/{withdraw}', [ManageWithdrawController::class, 'withdrawAccept'])->name('accept');
            Route::post('reject/{withdraw}', [ManageWithdrawController::class, 'withdrawReject'])->name('reject');
        });

        Route::middleware('permission:payments,admin')->prefix('payments')->name('payments.')->group(function () {
            Route::get('/{type}', [PaymentController::class, 'payment'])->name('index');
            Route::get('details/{id}', [PaymentController::class, 'details'])->name('details');

            Route::post('accept/{trx}', [PaymentController::class, 'accept'])->name('accept');
            Route::post('reject/{trx}', [PaymentController::class, 'reject'])->name('reject');
        });

        Route::middleware('permission:manage-theme,admin')->group(function () {
            Route::get('manage-theme', [ConfigurationController::class, 'manageTheme'])->name('manage.theme');
            Route::post('manage-theme/{name}', [ConfigurationController::class, 'themeUpdate'])->name('manage.theme.update');
            Route::post('backend-theme/{name}', [ConfigurationController::class, 'backendThemeUpdate'])->name('manage.backend.theme.update');
            Route::post('change/theme/color/{theme}', [ConfigurationController::class, 'themeColor'])->name('manage.theme.color');
            Route::post('theme/upload', [ConfigurationController::class, 'themeUpload'])->name('manage.theme.upload');
            Route::get('theme/download-template', [ConfigurationController::class, 'themeDownloadTemplate'])->name('manage.theme.download.template');
            Route::delete('theme/delete/{theme}', [ConfigurationController::class, 'themeDelete'])->name('manage.theme.delete');
            Route::post('theme/deactivate-all', [ConfigurationController::class, 'themeDeactivate'])->name('manage.theme.deactivate.all');
            
            // Backward compatibility: Access pagebuilder from Manage Theme
            Route::get('manage-theme/page-builder', [ConfigurationController::class, 'themePageBuilder'])->name('manage.theme.page-builder');
        });

        Route::middleware('permission:manage-frontend,admin')->group(function () {
            Route::get('pages', [PagesController::class, 'index'])->name('frontend.pages');
            Route::get('pages/create', [PagesController::class, 'pageCreate'])->name('frontend.pages.create');
            Route::post('pages/create', [PagesController::class, 'pageInsert']);
            Route::get('pages/edit/{id}', [PagesController::class, 'pageEdit'])->name('frontend.pages.edit');
            Route::post('pages/edit/{id}', [PagesController::class, 'pageUpdate']);
            Route::get('pages/search', [PagesController::class, 'index'])->name('frontend.search');
            Route::post('pages/delete/{id}', [PagesController::class, 'pageDelete'])->name('frontend.pages.delete');
            Route::get('page-builder/{id?}', [PagesController::class, 'pageBuilder'])->name('page-builder.index');

            Route::get('manage/section/{name}', [ManageSectionController::class, 'section'])->name('frontend.section.manage');
            Route::post('manage/section/{name}', [ManageSectionController::class, 'sectionContentUpdate']);
            Route::get('manage/element/{name}', [ManageSectionController::class, 'sectionElement'])->name('frontend.element');
            Route::get('manage/element/{name}/search', [ManageSectionController::class, 'section'])->name('frontend.element.search');
            Route::post('manage/element/{name}', [ManageSectionController::class, 'sectionElementCreate']);
            Route::get('edit/{name}/element/{element}', [ManageSectionController::class, 'editElement'])->name('frontend.element.edit');
            Route::post('edit/{name}/element/{element}', [ManageSectionController::class, 'updateElement']);
            Route::post('delete/{name}/element/{element}', [ManageSectionController::class, 'deleteElement'])->name('frontend.element.delete');


            Route::get('frontend/translate/{name}/{element}', [ManageSectionController::class,'translate'])->name('frontend.translate');
            Route::post('frontend/translate/{name}/{element}', [ManageSectionController::class,'translateUpdate']);

            // Backward compatibility: Access pagebuilder from Manage Pages
            Route::get('pages/{id}/page-builder', [PagesController::class, 'pageBuilder'])->name('pages.page-builder');
            Route::get('manage/section/{name}/page-builder', [ManageSectionController::class, 'pageBuilder'])->name('frontend.section.page-builder');
        });

        Route::middleware('permission:manage-deposit,admin')->group(function () {
            Route::get('deposit/log/{user?}', [LogController::class, 'depositLog'])->name('deposit.log');
            Route::get('deposit/{status}', [ManageDepositController::class, 'index'])->name('deposit');
            Route::post('deposit/{trx}/accept', [ManageDepositController::class, 'accept'])->name('deposit.accept');
            Route::post('deposit/{trx}/reject', [ManageDepositController::class, 'reject'])->name('deposit.reject');
            Route::get('deposit/{trx}/details', [ManageDepositController::class, 'details'])->name('deposit.details');
        });


        Route::middleware('permission:manage-logs,admin')->group(function () {
            Route::get('transaction-log/{user?}', [LogController::class, 'transaction'])->name('transaction');
            Route::get('payment-report/{user?}', [LogController::class, 'paymentReport'])->name('payment.report');
            Route::get('withdarw-report/{user?}', [LogController::class, 'withdarawReport'])->name('withdraw.report');
            Route::get('transfer/log', [LogController::class, 'transferLog'])->name('transfer.report');
            Route::get('commision/{user?}', [LogController::class, 'Commision'])->name('commision');
            Route::get('trade-log/{user?}', [LogController::class, 'tradeLog'])->name('trade');
        });


        Route::get('changeLang', [LanguageController::class, 'changeLang'])->name('changeLang');
        Route::get('all-notifications', [AdminController::class, 'notifications'])->name('notifications');
        Route::get('/mark-as-read', [AdminController::class, 'markNotification'])->name('markNotification');
        Route::post('/mark-as-read/{id}', [AdminController::class, 'SignlemarkNotification'])->name('markNotification.single');

        Route::get('subscribers', [AdminController::class, 'subscribers'])->name('subscribers');

        Route::post('subscribers/{email}', [AdminController::class, 'singleMail'])->name('subscribers.single');

        Route::post('bulk/mail', [AdminController::class, 'bulkMail'])->name('subscribers.bulk');

        Route::get('maintanace-mode', [DashboardController::class, 'maintanance'])->name('maintanace');

        Route::get('cacheclear', [ConfigurationController::class, 'cacheClear'])->name('general.cacheclear');
    });
});



Route::get('fire/email', [DashboardController::class, 'fireEmail'])->name('fire');
