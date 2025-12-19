<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register broadcasting routes with web middleware (includes auth and CSRF)
        Broadcast::routes(['middleware' => ['web']]);

        require base_path('routes/channels.php');
        
        // Load addon broadcast channels
        if (file_exists(base_path('addons/trading-management-addon/routes/channels.php'))) {
            require base_path('addons/trading-management-addon/routes/channels.php');
        }
    }
}
