<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // User Repository
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\UserRepository::class
        );

        // Signal Repository
        $this->app->bind(
            \App\Repositories\Contracts\SignalRepositoryInterface::class,
            \App\Repositories\SignalRepository::class
        );

        // Backtest Repository
        $this->app->bind(
            \App\Repositories\Contracts\BacktestRepositoryInterface::class,
            \App\Repositories\BacktestRepository::class
        );

        // Trading Bot Repository
        $this->app->bind(
            \App\Repositories\Contracts\TradingBotRepositoryInterface::class,
            \App\Repositories\TradingBotRepository::class
        );

        // Exchange Connection Repository
        $this->app->bind(
            \App\Repositories\Contracts\ExchangeConnectionRepositoryInterface::class,
            \App\Repositories\ExchangeConnectionRepository::class
        );
        
        // Register other existing repositories if needed
        $this->app->bind(
            \App\Repositories\Contracts\TradingRepositoryInterface::class,
            \App\Repositories\TradingRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Contracts\TradeRepositoryInterface::class,
            \App\Repositories\TradeRepository::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
