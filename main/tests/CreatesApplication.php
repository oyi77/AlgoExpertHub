<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Specific for Trading Management Addon testing
        if (class_exists(\Addons\TradingManagement\AddonServiceProvider::class)) {
            $app->register(\Addons\TradingManagement\AddonServiceProvider::class);
        }

        return $app;
    }
}
