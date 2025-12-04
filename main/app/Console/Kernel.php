<?php

namespace App\Console;

use App\Support\AddonRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        if (AddonRegistry::active('multi-channel-signal-addon') && AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'processing')) {
            // Process RSS feeds every 10 minutes
            $schedule->command('channel:process-rss')->everyTenMinutes();

            // Process web scraping channels every minute
            $schedule->command('channel:process-web-scrape')->everyMinute();

            // Process Telegram MTProto channels every 5 minutes
            $schedule->command('channel:process-telegram-mtproto')->everyFiveMinutes();

            // Process Trading Bot channels every 2 minutes
            $schedule->command('channel:process-trading-bot')->everyTwoMinutes();
        }

        // Trading Bot Signal Addon - Run worker continuously (or use supervisor/systemd)
        if (AddonRegistry::active('trading-bot-signal-addon') && AddonRegistry::moduleEnabled('trading-bot-signal-addon', 'signal_processing')) {
            // Note: For production, run as a daemon/service instead of scheduled command
            // $schedule->command('trading-bot:worker')->everyMinute();
        }

        // Trading Management Addon - Trading Bot Workers
        if (AddonRegistry::active('trading-management-addon')) {
            // Monitor trading bot workers every minute
            if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Jobs\MonitorTradingBotWorkersJob::class)) {
                $schedule->job(\Addons\TradingManagement\Modules\TradingBot\Jobs\MonitorTradingBotWorkersJob::class)
                    ->everyMinute()
                    ->withoutOverlapping();
            }
        }

        // Trading Execution Engine Addon
        if (AddonRegistry::active('trading-execution-engine-addon') && AddonRegistry::moduleEnabled('trading-execution-engine-addon', 'execution')) {
            // Monitor positions every minute
            if (class_exists(\Addons\TradingExecutionEngine\App\Jobs\MonitorPositionsJob::class)) {
            $schedule->job(\Addons\TradingExecutionEngine\App\Jobs\MonitorPositionsJob::class)
                ->everyMinute()
                ->withoutOverlapping();
            }

            // Update analytics daily
            if (class_exists(\Addons\TradingExecutionEngine\App\Jobs\UpdateAnalyticsJob::class)) {
            $schedule->job(\Addons\TradingExecutionEngine\App\Jobs\UpdateAnalyticsJob::class)
                ->daily()
                ->at('00:00');
            }
        }

        // Smart Risk Management Addon
        if (AddonRegistry::active('smart-risk-management-addon') && AddonRegistry::moduleEnabled('smart-risk-management-addon', 'srm_engine')) {
            // Update performance scores daily at 1 AM
            if (class_exists(\Addons\SmartRiskManagement\App\Jobs\UpdatePerformanceScoresJob::class)) {
                $schedule->job(\Addons\SmartRiskManagement\App\Jobs\UpdatePerformanceScoresJob::class)
                    ->daily()
                    ->at('01:00')
                    ->withoutOverlapping();
            }

            // Monitor drawdown every 5 minutes
            if (class_exists(\Addons\SmartRiskManagement\App\Jobs\MonitorDrawdownJob::class)) {
                $schedule->job(\Addons\SmartRiskManagement\App\Jobs\MonitorDrawdownJob::class)
                    ->everyFiveMinutes()
                    ->withoutOverlapping();
            }

            // Retrain models weekly (Sunday at 3 AM)
            if (class_exists(\Addons\SmartRiskManagement\App\Jobs\RetrainModelsJob::class)) {
                $schedule->job(\Addons\SmartRiskManagement\App\Jobs\RetrainModelsJob::class)
                    ->weekly()
                    ->sundays()
                    ->at('03:00')
                    ->withoutOverlapping();
            }
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
